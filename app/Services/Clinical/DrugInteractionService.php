<?php

namespace App\Services\Clinical;

use App\Contracts\DrugInteractionChecker;

class DrugInteractionService implements DrugInteractionChecker
{
    /**
     * Known drug interactions database.
     * In production, this would be a separate database or API.
     */
    protected array $interactions = [
        // Warfarin interactions
        'warfarin+nsaids' => [
            'severity' => 'major',
            'description' => 'Increased risk of bleeding. NSAIDs enhance anticoagulant effect.',
            'recommendation' => 'Monitor INR closely. Consider alternative analgesic.',
        ],
        'warfarin+aspirin' => [
            'severity' => 'major',
            'description' => 'Increased risk of bleeding. Additive anticoagulant effects.',
            'recommendation' => 'Avoid combination if possible. Monitor for bleeding signs.',
        ],
        'warfarin+amiodarone' => [
            'severity' => 'major',
            'description' => 'Amiodarone inhibits warfarin metabolism, increasing INR.',
            'recommendation' => 'Reduce warfarin dose by 30-50%. Monitor INR frequently.',
        ],
        'warfarin+fluconazole' => [
            'severity' => 'major',
            'description' => 'Fluconazole inhibits CYP2C9, increasing warfarin levels.',
            'recommendation' => 'Reduce warfarin dose. Monitor INR.',
        ],

        // ACE Inhibitors
        'lisinopril+spironolactone' => [
            'severity' => 'moderate',
            'description' => 'Increased risk of hyperkalemia.',
            'recommendation' => 'Monitor potassium levels regularly.',
        ],
        'enalapril+potassium' => [
            'severity' => 'moderate',
            'description' => 'Risk of hyperkalemia with potassium supplements.',
            'recommendation' => 'Monitor serum potassium.',
        ],

        // Metformin
        'metformin+cimetidine' => [
            'severity' => 'moderate',
            'description' => 'Cimetidine may increase metformin levels.',
            'recommendation' => 'Monitor for metformin toxicity. Consider alternative H2 blocker.',
        ],
        'metformin+contrast' => [
            'severity' => 'major',
            'description' => 'Risk of lactic acidosis with iodinated contrast.',
            'recommendation' => 'Discontinue metformin before contrast. Reassess 48h after.',
        ],

        // SSRIs
        'fluoxetine+tramadol' => [
            'severity' => 'major',
            'description' => 'Risk of serotonin syndrome.',
            'recommendation' => 'Avoid combination. Use alternative analgesic.',
        ],
        'sertraline+maoi' => [
            'severity' => 'contraindicated',
            'description' => 'Contraindicated. High risk of serotonin syndrome.',
            'recommendation' => 'Do not combine. 14-day washout required.',
        ],
        'ssri+maoi' => [
            'severity' => 'contraindicated',
            'description' => 'Contraindicated. High risk of serotonin syndrome.',
            'recommendation' => 'Do not combine. 14-day washout required.',
        ],
        'citalopram+qt' => [
            'severity' => 'major',
            'description' => 'Risk of QT prolongation with other QT-prolonging drugs.',
            'recommendation' => 'Monitor ECG. Consider alternative SSRI.',
        ],

        // Antibiotics
        'ciprofloxacin+theophylline' => [
            'severity' => 'major',
            'description' => 'Ciprofloxacin increases theophylline levels.',
            'recommendation' => 'Monitor theophylline levels. Reduce dose if needed.',
        ],
        'clarithromycin+statin' => [
            'severity' => 'major',
            'description' => 'Increased risk of myopathy/rhabdomyolysis.',
            'recommendation' => 'Temporarily discontinue statin during treatment.',
        ],

        // Opioids
        'morphine+benzodiazepine' => [
            'severity' => 'major',
            'description' => 'Risk of profound CNS and respiratory depression.',
            'recommendation' => 'Use lowest effective doses. Have naloxone available.',
        ],
        'tramadol+ssri' => [
            'severity' => 'major',
            'description' => 'Risk of serotonin syndrome.',
            'recommendation' => 'Avoid combination if possible.',
        ],

        // Diuretics
        'furosemide+aminoglycoside' => [
            'severity' => 'major',
            'description' => 'Increased risk of ototoxicity and nephrotoxicity.',
            'recommendation' => 'Monitor renal function and hearing.',
        ],
        'hydrochlorothiazide+lithium' => [
            'severity' => 'major',
            'description' => 'Reduced lithium clearance, risk of toxicity.',
            'recommendation' => 'Monitor lithium levels closely.',
        ],
    ];

    /**
     * Check for drug interactions.
     */
    public function checkInteractions(array $medications): array
    {
        $interactions = [];

        foreach ($medications as $i => $drug1) {
            foreach ($medications as $j => $drug2) {
                if ($i >= $j) {
                    continue;
                }

                $interaction = $this->getInteraction($drug1, $drug2);

                if ($interaction) {
                    $interactions[] = array_merge($interaction, [
                        'drug1' => $drug1,
                        'drug2' => $drug2,
                    ]);
                }
            }
        }

        return $interactions;
    }

    /**
     * Get interaction between two drugs.
     */
    public function getInteraction(string $drug1, string $drug2): ?array
    {
        $key1 = $this->normalizeKey($drug1, $drug2);
        $key2 = $this->normalizeKey($drug2, $drug1);

        if (isset($this->interactions[$key1])) {
            return $this->interactions[$key1];
        }

        if (isset($this->interactions[$key2])) {
            return $this->interactions[$key2];
        }

        // Check for partial matches (drug class interactions)
        return $this->checkClassInteractions($drug1, $drug2);
    }

    /**
     * Normalize drug names for key lookup.
     */
    protected function normalizeKey(string $drug1, string $drug2): string
    {
        $d1 = strtolower(trim($drug1));
        $d2 = strtolower(trim($drug2));

        // Map common drug names
        $mappings = [
            'ibuprofen' => 'nsaids',
            'naproxen' => 'nsaids',
            'diclofenac' => 'nsaids',
            'lisinopril' => 'ace',
            'enalapril' => 'ace',
            'ramipril' => 'ace',
            'fluoxetine' => 'ssri',
            'sertraline' => 'ssri',
            'citalopram' => 'ssri',
            'atorvastatin' => 'statin',
            'simvastatin' => 'statin',
            'rosuvastatin' => 'statin',
        ];

        $d1 = $mappings[$d1] ?? $d1;
        $d2 = $mappings[$d2] ?? $d2;

        return "{$d1}+{$d2}";
    }

    /**
     * Check for class-level interactions.
     */
    protected function checkClassInteractions(string $drug1, string $drug2): ?array
    {
        $key = $this->normalizeKey($drug1, $drug2);

        // Check for warfarin + any NSAID
        if (str_contains($key, 'warfarin') && str_contains($key, 'nsaid')) {
            return $this->interactions['warfarin+nsaids'];
        }

        // Check for ACE + potassium-sparing diuretics
        if (str_contains($key, 'ace') && str_contains($key, 'spironolactone')) {
            return $this->interactions['lisinopril+spironolactone'];
        }

        // Check for ACE + potassium
        if (str_contains($key, 'ace') && str_contains($key, 'potassium')) {
            return $this->interactions['enalapril+potassium'];
        }

        // Check for opioid + benzo
        if ((str_contains($key, 'morphine') || str_contains($key, 'opioid')) && str_contains($key, 'benzodiazepine')) {
            return $this->interactions['morphine+benzodiazepine'];
        }

        return null;
    }

    /**
     * Get severity level as numeric for sorting.
     */
    public function getSeverityLevel(string $severity): int
    {
        return match ($severity) {
            'contraindicated' => 4,
            'major' => 3,
            'moderate' => 2,
            'minor' => 1,
            default => 0,
        };
    }
}
