<?php

/**
 * ============================================================================
 * LARAVEL MONEY PACKAGE - Production Ready
 * ============================================================================
 * 
 * Architecture:
 * - Entrée: format humain (10.50)
 * - Interne: minor unit (1050) en string
 * - Calculs: BCMath sur minor unit
 * - Sortie: format humain (10.50)
 * - DB: integer (1050)
 * 
 * Invariants:
 * - Immutabilité totale
 * - BCMath uniquement
 * - Pas de float
 * - Négatifs rejetés par défaut
 * - Arrondi bancaire standard (HALF_UP)
 */

return [
    /**
     * Précision de la devise (nombre de décimales)
     * 
     * Exemples:
     * - EUR, USD, GBP: 2 (centimes)
     * - JPY, KRW: 0 (pas de subdivision)
     * - KWD, BHD, OMR: 3 (fils)
     * - MRU: 1 (khoums)
     */
    'precision' => env('MONEY_PRECISION', 2),

    /**
     * Mode d'arrondi (constantes PHP_ROUND_*)
     * 
     * PHP_ROUND_HALF_UP   : 0.385 → 0.39 (standard bancaire)
     * PHP_ROUND_HALF_DOWN : 0.385 → 0.38 (conservateur)
     * PHP_ROUND_DOWN      : 0.389 → 0.38 (floor)
     * PHP_ROUND_UP        : 0.381 → 0.39 (ceil)
     * PHP_ROUND_HALF_EVEN : 0.385 → 0.38, 0.375 → 0.38 (banker's rounding)
     */
    'rounding_mode' => PHP_ROUND_HALF_UP,

    /**
     * Autoriser les montants négatifs ?
     * 
     * false : Sécurité stricte (rejette les négatifs)
     * true  : Autorise découverts, dettes, etc.
     */
    'allow_negative' => false,
];
