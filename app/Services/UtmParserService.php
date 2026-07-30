<?php

namespace App\Services;

class UtmParserService
{
    /**
     * Classify traffic type based on UTM parameters.
     *
     * Business Logic:
     * - IF utm_medium contains "cpc", "ppc", "paid", "ads"
     *   OR utm_source contains "fbads", "googleads"
     *   → traffic_type = "Ads"
     * - IF has UTM source/medium but not paid indicator → "Organik"
     * - IF no UTM data at all → "Direct"
     */
    public function classify(?string $utmSource, ?string $utmMedium): string
    {
        $paidKeywords = ['cpc', 'ppc', 'paid', 'ads'];
        $paidSources = ['fbads', 'googleads', 'facebookads', 'instagramads'];

        // Check paid indicators
        if ($utmMedium) {
            $mediumLower = strtolower($utmMedium);
            foreach ($paidKeywords as $keyword) {
                if (str_contains($mediumLower, $keyword)) {
                    return 'ads';
                }
            }
        }

        if ($utmSource) {
            $sourceLower = strtolower($utmSource);
            foreach ($paidSources as $paidSource) {
                if (str_contains($sourceLower, $paidSource)) {
                    return 'ads';
                }
            }
        }

        // Has UTM but not paid → Organik
        if ($utmSource || $utmMedium) {
            return 'organik';
        }

        // No UTM at all → Direct
        return 'direct';
    }
}
