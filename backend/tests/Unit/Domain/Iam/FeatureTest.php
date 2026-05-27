<?php

declare(strict_types=1);

use App\Domain\Iam\Feature;

it('exposes stable backing values', function (): void {
    expect(Feature::MultiplePlaces->value)->toBe('multiple_places');
    expect(Feature::WeeklyDigest->value)->toBe('weekly_digest');
    expect(Feature::NegativeAlertsTelegram->value)->toBe('negative_alerts_telegram');
    expect(Feature::NegativeAlertsEmail->value)->toBe('negative_alerts_email');
    expect(Feature::CustomBranding->value)->toBe('custom_branding');
    expect(Feature::QrThemes->value)->toBe('qr_themes');
    expect(Feature::CsvExportReviews->value)->toBe('csv_export_reviews');
    expect(Feature::ApiAccess->value)->toBe('api_access');
    expect(Feature::PrioritySupport->value)->toBe('priority_support');
});

it('returns a non-empty russian label for each case', function (): void {
    foreach (Feature::cases() as $feature) {
        expect($feature->label())->not->toBe('');
    }
});

it('rejects unknown values via tryFrom', function (): void {
    expect(Feature::tryFrom('unknown'))->toBeNull();
    expect(Feature::tryFrom(''))->toBeNull();
});
