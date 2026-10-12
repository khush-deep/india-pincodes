<?php

namespace Khush\IndiaPincodes;

/**
 * The 28 states and 8 union territories of India, keyed by ISO 3166-2:IN code, sorted by name.
 * Codes are the ones current since the ISO update of 23 Nov 2023.
 */
class IndiaState
{
    public const NAMES_BY_CODE = [
        'AN' => 'Andaman and Nicobar Islands',
        'AP' => 'Andhra Pradesh',
        'AR' => 'Arunachal Pradesh',
        'AS' => 'Assam',
        'BR' => 'Bihar',
        'CH' => 'Chandigarh',
        'CG' => 'Chhattisgarh',
        'DH' => 'Dadra and Nagar Haveli and Daman and Diu',
        'DL' => 'Delhi',
        'GA' => 'Goa',
        'GJ' => 'Gujarat',
        'HR' => 'Haryana',
        'HP' => 'Himachal Pradesh',
        'JK' => 'Jammu and Kashmir',
        'JH' => 'Jharkhand',
        'KA' => 'Karnataka',
        'KL' => 'Kerala',
        'LA' => 'Ladakh',
        'LD' => 'Lakshadweep',
        'MP' => 'Madhya Pradesh',
        'MH' => 'Maharashtra',
        'MN' => 'Manipur',
        'ML' => 'Meghalaya',
        'MZ' => 'Mizoram',
        'NL' => 'Nagaland',
        'OD' => 'Odisha',
        'PY' => 'Puducherry',
        'PB' => 'Punjab',
        'RJ' => 'Rajasthan',
        'SK' => 'Sikkim',
        'TN' => 'Tamil Nadu',
        'TS' => 'Telangana',
        'TR' => 'Tripura',
        'UP' => 'Uttar Pradesh',
        'UK' => 'Uttarakhand',
        'WB' => 'West Bengal',
    ];

    /**
     * Codes ISO used before, so a code you stored earlier can still be read. DN and DD were merged
     * into DH in 2020, the rest changed in the 2023 update.
     */
    public const OLD_CODES_TO_CODE = [
        'CT' => 'CG',
        'DD' => 'DH',
        'DN' => 'DH',
        'OR' => 'OD',
        'TG' => 'TS',
        'UT' => 'UK',
    ];
}
