<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RDA - {{ $patient->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 14pt;
            color: #1a365d;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 10pt;
            color: #666;
        }
        
        .institution-info {
            font-size: 9pt;
            color: #666;
            margin-bottom: 10px;
        }
        
        .rda-title {
            background: #1a365d;
            color: white;
            padding: 8px 15px;
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            background: #e2e8f0;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 10pt;
            color: #1a365d;
            margin-bottom: 10px;
            border-left: 4px solid #1a365d;
        }
        
        .patient-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .patient-info {
            width: 60%;
        }
        
        .patient-ids {
            width: 35%;
            text-align: right;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            width: 120px;
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            flex: 1;
        }
        
        .vital-signs-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        
        .vital-item {
            background: #f7fafc;
            padding: 8px;
            border-radius: 4px;
        }
        
        .vital-label {
            font-size: 8pt;
            color: #666;
        }
        
        .vital-value {
            font-size: 12pt;
            font-weight: bold;
            color: #1a365d;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            font-size: 9pt;
        }
        
        th {
            background: #f7fafc;
            font-weight: bold;
            color: #1a365d;
        }
        
        .allergy-alert {
            background: #fed7d7;
            border: 1px solid #fc8181;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .allergy-alert h4 {
            color: #c53030;
            margin-bottom: 5px;
        }
        
        .signature-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .signature-box {
            width: 50%;
            text-align: center;
            margin: 0 auto;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60pt;
            color: rgba(26, 54, 93, 0.05);
            white-space: nowrap;
            pointer-events: none;
        }
        
        .compliance-note {
            font-size: 7pt;
            color: #999;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="watermark">VESPORT HCE</div>
    
    <!-- Header -->
    <div class="header">
        <h1>{{ $institution['name'] ?? 'VESPORT HCE' }}</h1>
        <div class="institution-info">
            @if($institution['nit'] ?? null) NIT: {{ $institution['nit'] }} &bull; @endif
            @if($institution['address'] ?? null) {{ $institution['address'] }} &bull; @endif
            @if($institution['phone'] ?? null) Tel: {{ $institution['phone'] }} @endif
        </div>
        <div class="subtitle">Historia Clínica Electrónica</div>
    </div>
    
    <div class="rda-title">RESUMEN DE ATENCIÓN - RDA</div>
    
    <!-- Patient Information -->
    <div class="section">
        <div class="section-title">1. INFORMACIÓN DEL PACIENTE</div>
        
        <div class="patient-header">
            <div class="patient-info">
                <div class="info-row">
                    <span class="info-label">Nombre completo:</span>
                    <span class="info-value">{{ $patient->full_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Documento:</span>
                    <span class="info-value">{{ $patient->document_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha de Nacimiento:</span>
                    <span class="info-value">{{ $patient->birth_date?->format('d/m/Y') ?? '-' }} ({{ $patient->birth_date?->age ?? '-' }} años)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Género:</span>
                    <span class="info-value">{{ $patient->gender === 'male' ? 'Masculino' : ($patient->gender === 'female' ? 'Femenino' : 'Otro') }}</span>
                </div>
            </div>
            
            <div class="patient-ids">
                <div class="info-row">
                    <span class="info-label">ID Paciente:</span>
                    <span class="info-value">{{ $patient->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Atención:</span>
                    <span class="info-value">{{ $encounter->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. Consulta:</span>
                    <span class="info-value">{{ $encounter->id }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Allergies Alert -->
    @if($allergies->count() > 0)
    <div class="allergy-alert">
        <h4>⚠️ ALERTA DE ALERGIAS</h4>
        <p>{{ $allergies->pluck('code')->filter()->join(', ') ?: $allergies->pluck('note')->filter()->join(', ') }}</p>
    </div>
    @endif
    
    <!-- Reason for Visit -->
    <div class="section">
        <div class="section-title">2. MOTIVO DE CONSULTA</div>
        <p style="padding: 10px; background: #f7fafc; border-radius: 4px;">
            {{ $encounter->reason ?? 'No registrado' }}
        </p>
        @if($encounter->note)
        <p style="margin-top: 10px; padding: 10px;">
            <strong>Anamnesis:</strong><br>
            {{ $encounter->note }}
        </p>
        @endif
    </div>
    
    <!-- Vital Signs -->
    @if(!empty($vitalSigns))
    <div class="section">
        <div class="section-title">3. SIGNOS VITALES</div>
        <div class="vital-signs-grid">
            @isset($vitalSigns['systolic_bp'])
            <div class="vital-item">
                <div class="vital-label">Presión Arterial</div>
                <div class="vital-value">{{ $vitalSigns['systolic_bp']['value'] ?? '-' }}/{{ $vitalSigns['diastolic_bp']['value'] ?? '-' }} mmHg</div>
            </div>
            @endisset
            @isset($vitalSigns['heart_rate'])
            <div class="vital-item">
                <div class="vital-label">Frec. Cardíaca</div>
                <div class="vital-value">{{ $vitalSigns['heart_rate']['value'] }} {{ $vitalSigns['heart_rate']['unit'] }}</div>
            </div>
            @endisset
            @isset($vitalSigns['respiratory_rate'])
            <div class="vital-item">
                <div class="vital-label">Frec. Respiratoria</div>
                <div class="vital-value">{{ $vitalSigns['respiratory_rate']['value'] }} {{ $vitalSigns['respiratory_rate']['unit'] }}</div>
            </div>
            @endisset
            @isset($vitalSigns['temperature'])
            <div class="vital-item">
                <div class="vital-label">Temperatura</div>
                <div class="vital-value">{{ $vitalSigns['temperature']['value'] }} {{ $vitalSigns['temperature']['unit'] }}</div>
            </div>
            @endisset
            @isset($vitalSigns['spo2'])
            <div class="vital-item">
                <div class="vital-label">SpO2</div>
                <div class="vital-value">{{ $vitalSigns['spo2']['value'] }}{{ $vitalSigns['spo2']['unit'] }}</div>
            </div>
            @endisset
            @isset($vitalSigns['weight'])
            <div class="vital-item">
                <div class="vital-label">Peso</div>
                <div class="vital-value">{{ $vitalSigns['weight']['value'] }} {{ $vitalSigns['weight']['unit'] }}</div>
            </div>
            @endisset
            @isset($vitalSigns['height'])
            <div class="vital-item">
                <div class="vital-label">Talla</div>
                <div class="vital-value">{{ $vitalSigns['height']['value'] }} {{ $vitalSigns['height']['unit'] }}</div>
            </div>
            @endisset
            @isset($vitalSigns['bmi'])
            <div class="vital-item">
                <div class="vital-label">IMC</div>
                <div class="vital-value">{{ $vitalSigns['bmi']['value'] }} {{ $vitalSigns['bmi']['unit'] }}</div>
            </div>
            @endisset
        </div>
    </div>
    @endif
    
    <!-- Diagnoses -->
    @if($diagnoses->count() > 0)
    <div class="section">
        <div class="section-title">4. DIAGNÓSTICOS (CIE-10)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%">Código</th>
                    <th style="width: 60%">Descripción</th>
                    <th style="width: 25%">Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnoses as $diagnosis)
                <tr>
                    <td>{{ $diagnosis->code ?? '-' }}</td>
                    <td>{{ $diagnosis->note ?? '-' }}</td>
                    <td>{{ $diagnosis->category === 'principal' ? 'Principal' : 'Secundario' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Medications -->
    @if($medications->count() > 0)
    <div class="section">
        <div class="section-title">5. MEDICAMENTOS PRESCRITOS</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%">Medicamento</th>
                    <th style="width: 15%">Dosis</th>
                    <th style="width: 20%">Frecuencia</th>
                    <th style="width: 15%">Duración</th>
                    <th style="width: 20%">Instrucciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medications as $med)
                <tr>
                    <td>{{ $med->medication ?? '-' }}</td>
                    <td>{{ $med->dosage ?? '-' }}</td>
                    <td>{{ $med->frequency ?? '-' }}</td>
                    <td>{{ $med->duration ?? '-' }}</td>
                    <td>{{ $med->instructions ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div style="margin-bottom: 20px;">
                <p><strong>Atendido por:</strong> {{ $generatedBy->name ?? $generatedBy->email ?? '-' }}</p>
                <p><strong>Registro Médico:</strong> {{ $generatedBy->medical_id ?? '-' }}</p>
            </div>
            <div class="signature-line">
                Firma del Profesional
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Documento generado el {{ $generatedAt->format('d/m/Y H:i:s') }}</p>
        <p>Vespot HCE - Cumple con Resolución 1995 de 1999 y Ley 1581 de 2012</p>
    </div>
    
    <div class="compliance-note">
        <p>Este documento hace parte de la Historia Clínica Electrónica y cumple con los requisitos de conservación</p>
        <p>establecidos en la Resolución 1995 de 1999 (Art. 14: conservación mínima de 20 años)</p>
    </div>
</body>
</html>