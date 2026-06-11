<?php
// ============================================
// Algemene helper functies
// ============================================

/**
 * Bereken punten voor een voorspelling op basis van de werkelijke uitslag.
 *
 * Puntensysteem:
 *   - Exacte uitslag geraden: 3 punten
 *   - Goede winnaar + juist doelsaldo: 2 punten
 *   - Alleen goede winnaar / gelijkspel: 1 punt
 *   - Anders: 0 punten
 *
 * @return int|null null als de wedstrijd nog geen uitslag heeft
 */
function calculatePoints(
    ?int $actualHome,
    ?int $actualAway,
    int $predictedHome,
    int $predictedAway
): ?int {
    if ($actualHome === null || $actualAway === null) {
        return null;
    }

    if ($predictedHome === $actualHome && $predictedAway === $actualAway) {
        return 3;
    }

    $actualDiff = $actualHome - $actualAway;
    $predictedDiff = $predictedHome - $predictedAway;

    $correctWinner = ($actualDiff > 0 && $predictedDiff > 0)
        || ($actualDiff < 0 && $predictedDiff < 0)
        || ($actualDiff === 0 && $predictedDiff === 0);

    if (!$correctWinner) {
        return 0;
    }

    if (abs($actualDiff) === abs($predictedDiff)) {
        return 2;
    }

    return 1;
}
