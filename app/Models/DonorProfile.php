<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonationOutcome;
use Database\Factories\DonorProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DonorProfile extends Model
{
    /** @use HasFactory<DonorProfileFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'blood_group',
        'last_donated_on',
        'notes_for_staff',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_donated_on' => 'date',
        ];
    }

    /**
     * Get the user that owns the donor profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Derive last_donated_on from the latest 'donated' record in EAT.
     */
    public static function refreshLastDonation(User|self|int $donor): void
    {
        $profile = match (true) {
            $donor instanceof self => $donor,
            $donor instanceof User => $donor->donorProfile ?? self::firstOrCreate(['user_id' => $donor->id]),
            default => self::firstOrCreate(['user_id' => $donor]),
        };

        $latest = DonationRecord::query()
            ->where('user_id', $profile->user_id)
            ->where('outcome', DonationOutcome::Donated)
            ->latest('recorded_at')
            ->first();

        $profile->last_donated_on = $latest
            ? Carbon::parse($latest->recorded_at)->setTimezone('Africa/Kampala')->toDateString()
            : null;

        $profile->save();
    }
}
