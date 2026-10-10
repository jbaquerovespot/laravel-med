<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PatientFingerprint extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Finger identifier constants.
     */
    public const FINGER_RIGHT_THUMB = 'right_thumb';

    public const FINGER_RIGHT_INDEX = 'right_index';

    public const FINGER_RIGHT_MIDDLE = 'right_middle';

    public const FINGER_RIGHT_RING = 'right_ring';

    public const FINGER_RIGHT_LITTLE = 'right_little';

    public const FINGER_LEFT_THUMB = 'left_thumb';

    public const FINGER_LEFT_INDEX = 'left_index';

    public const FINGER_LEFT_MIDDLE = 'left_middle';

    public const FINGER_LEFT_RING = 'left_ring';

    public const FINGER_LEFT_LITTLE = 'left_little';

    /**
     * Minimum quality score for enrollment.
     */
    public const MIN_QUALITY_SCORE = 60;

    protected $fillable = [
        'patient_id',
        'finger_identifier',
        'encrypted_template',
        'template_hash',
        'quality_score',
        'device_serial',
        'device_model',
        'consent_date',
        'consent_document',
        'is_active',
        'capture_attempts',
        'last_used_at',
    ];

    protected $casts = [
        'consent_date' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'quality_score' => 'integer',
        'capture_attempts' => 'integer',
    ];

    protected $hidden = [
        'encrypted_template',
        'template_hash',
    ];

    /**
     * Get the patient that owns the fingerprint.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get all available finger identifiers.
     */
    public static function getFingerOptions(): array
    {
        return [
            self::FINGER_RIGHT_THUMB => __('Pulgar derecho'),
            self::FINGER_RIGHT_INDEX => __('Índice derecho'),
            self::FINGER_RIGHT_MIDDLE => __('Medio derecho'),
            self::FINGER_RIGHT_RING => __('Anular derecho'),
            self::FINGER_RIGHT_LITTLE => __('Meñique derecho'),
            self::FINGER_LEFT_THUMB => __('Pulgar izquierdo'),
            self::FINGER_LEFT_INDEX => __('Índice izquierdo'),
            self::FINGER_LEFT_MIDDLE => __('Medio izquierdo'),
            self::FINGER_LEFT_RING => __('Anular izquierdo'),
            self::FINGER_LEFT_LITTLE => __('Meñique izquierdo'),
        ];
    }

    /**
     * Get the display name for the finger identifier.
     */
    public function getFingerNameAttribute(): string
    {
        return self::getFingerOptions()[$this->finger_identifier] ?? $this->finger_identifier;
    }

    /**
     * Decrypt and return the fingerprint template.
     */
    public function getTemplate(): string
    {
        return Crypt::decryptString($this->encrypted_template);
    }

    /**
     * Set the encrypted fingerprint template.
     */
    public function setTemplate(string $template): void
    {
        $this->encrypted_template = Crypt::encryptString($template);
        $this->template_hash = hash('sha256', $template);
    }

    /**
     * Check if the fingerprint quality is acceptable.
     */
    public function hasAcceptableQuality(): bool
    {
        return $this->quality_score >= self::MIN_QUALITY_SCORE;
    }

    /**
     * Record a successful authentication.
     */
    public function recordAuthentication(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope to get only active fingerprints.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get fingerprints with acceptable quality.
     */
    public function scopeAcceptableQuality($query)
    {
        return $query->where('quality_score', '>=', self::MIN_QUALITY_SCORE);
    }
}
