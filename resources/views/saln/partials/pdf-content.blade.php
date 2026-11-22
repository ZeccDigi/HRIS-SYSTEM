@php
    $saln = clone $saln;
    $identityDefaults = array_fill_keys(\App\Services\SalnPdsDefaultsService::FIELDS, 'N/A');
    $identityService = app(\App\Services\SalnPdsDefaultsService::class);
    $saln->declarant_info = $identityService->merge($saln->declarant_info, $identityDefaults);
    $saln->spouse_info = $identityService->merge($saln->spouse_info, $identityDefaults);
    $realProperties = collect($saln->real_properties ?? [])->values();
    $personalProperties = collect($saln->personal_properties ?? [])->values();
    $liabilities = collect($saln->liabilities ?? [])->values();
    $businessInterests = collect($saln->business_interests ?? [])->values();
    $relativesInGov = collect($saln->relatives_in_gov ?? [])->values();
    $children = collect($saln->children ?? [])->values();
    $childrenRows = $children->isEmpty() ? collect(array_fill(0, 3, [])) : $children;
    $realPropertyRows = $realProperties->isEmpty() ? collect(array_fill(0, 4, [])) : $realProperties;
    $personalPropertyRows = $personalProperties->isEmpty() ? collect(array_fill(0, 5, [])) : $personalProperties;
    $liabilityRows = $liabilities->isEmpty() ? collect(array_fill(0, 3, [])) : $liabilities;
    $businessRows = $businessInterests->isEmpty() ? collect(array_fill(0, 3, [])) : $businessInterests;
    $relativeRows = $relativesInGov->isEmpty() ? collect(array_fill(0, 4, [])) : $relativesInGov;
    $realSubtotal = $realProperties->sum(fn ($prop) => (float) ($prop['acquisition_cost'] ?? 0));
    $personalSubtotal = $personalProperties->sum(fn ($prop) => (float) ($prop['acquisition_cost'] ?? 0));
    $filingStatus = strtolower((string) ($saln->filing_status ?? ''));
    $multipleSpouses = array_values(array_filter($saln->declarant_info['multiple_spouses'] ?? []));
    $multipleMarriagesNA = ($saln->declarant_info['multiple_marriages_not_applicable'] ?? null) !== false && count($multipleSpouses) === 0;
    $dateAccomplished = $saln->declarant_info['date_accomplished'] ?? null;
    $dateAccomplishedText = $dateAccomplished ? \Carbon\Carbon::parse($dateAccomplished)->format('F d, Y') : '';
    $declarantSignatureUrl = $saln->employee?->effective_signature_url;
    $filingType = strtolower(str_replace([' ', '-'], '_', (string) ($saln->type_of_filing ?? '')));
    $isAssumptionFiling = in_array($filingType, ['assumption', 'assumption_of_office'], true);
    $isAnnualFiling = in_array($filingType, ['annual', 'annual_filing'], true);
    $isExitFiling = in_array($filingType, ['exit', 'exit_from_service'], true);
@endphp

<div class="saln-pdf-document">
    <div class="saln-pdf-meta">
        <strong>2025 SALN Form</strong><br>
        Per CSC Resolution No. 2500632<br>
        Promulgated on 25 June 2025
    </div>

    <h1 class="saln-pdf-title">Sworn Statement of Assets, Liabilities, and Net Worth</h1>
    <p class="saln-pdf-subtitle">(As required by R.A. No. 6713)</p>

    <p class="saln-pdf-compliance-title">Compliance For:</p>
    <table class="saln-pdf-compliance">
        <tr>
            <td>
                <span class="saln-pdf-box">{{ $isAssumptionFiling ? 'X' : '' }}</span>
                Assumption of office as of {{ $isAssumptionFiling ? $saln->as_of_date->format('F d, Y') : '____________' }}
            </td>
            <td>
                <span class="saln-pdf-box">{{ $isAnnualFiling ? 'X' : '' }}</span>
                Annual filing as of December 31, {{ $isAnnualFiling ? $saln->as_of_date->format('Y') : '_____' }}
            </td>
            <td>
                <span class="saln-pdf-box">{{ $isExitFiling ? 'X' : '' }}</span>
                Exit as of {{ $isExitFiling ? $saln->as_of_date->format('F d, Y') : '____________' }}
            </td>
        </tr>
    </table>

    <div class="saln-pdf-double-rule"></div>

    <table class="saln-pdf-identity">
        <colgroup>
            <col style="width:20mm">
            <col style="width:32mm">
            <col style="width:42mm">
            <col style="width:18mm">
            <col style="width:8mm">
            <col style="width:39mm">
            <col style="width:auto">
        </colgroup>
        <tr>
            <td rowspan="4" class="saln-pdf-role">DECLARANT:</td>
            <td rowspan="2" class="saln-pdf-name-cell"><span class="saln-pdf-line">{{ $saln->declarant_info['family_name'] ?? '' }}</span><span class="saln-pdf-small-label">(Family Name)</span></td>
            <td rowspan="2" class="saln-pdf-name-cell"><span class="saln-pdf-line">{{ $saln->declarant_info['first_name'] ?? '' }}</span><span class="saln-pdf-small-label">(First Name)</span></td>
            <td rowspan="2" class="saln-pdf-name-cell"><span class="saln-pdf-line">{{ $saln->declarant_info['middle_initial'] ?? '' }}</span><span class="saln-pdf-small-label">(M.I.)</span></td>
            <td rowspan="4"></td>
            <td class="saln-pdf-side-label">POSITION:</td>
            <td class="saln-pdf-side-value">{{ $saln->declarant_info['position'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="saln-pdf-side-label">AGENCY/OFFICE:</td>
            <td class="saln-pdf-side-value">{{ $saln->declarant_info['agency'] ?? $saln->declarant_info['agency_office'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="3"><span class="saln-pdf-line">&nbsp;</span></td>
            <td class="saln-pdf-side-label">OFFICE ADDRESS:</td>
            <td class="saln-pdf-side-value">{{ $saln->declarant_info['office_address'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="3"><span class="saln-pdf-line">&nbsp;</span></td>
            <td></td>
            <td class="saln-pdf-side-value">&nbsp;</td>
        </tr>
        <tr>
            <td rowspan="4" class="saln-pdf-role">SPOUSE:</td>
            <td rowspan="2" class="saln-pdf-name-cell"><span class="saln-pdf-line">{{ $saln->spouse_info['family_name'] ?? '' }}</span><span class="saln-pdf-small-label">(Family Name)</span></td>
            <td rowspan="2" class="saln-pdf-name-cell"><span class="saln-pdf-line">{{ $saln->spouse_info['first_name'] ?? '' }}</span><span class="saln-pdf-small-label">(First Name)</span></td>
            <td rowspan="2" class="saln-pdf-name-cell"><span class="saln-pdf-line">{{ $saln->spouse_info['middle_initial'] ?? '' }}</span><span class="saln-pdf-small-label">(M.I.)</span></td>
            <td rowspan="4"></td>
            <td class="saln-pdf-side-label">POSITION:</td>
            <td class="saln-pdf-side-value">{{ $saln->spouse_info['position'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="saln-pdf-side-label">AGENCY/OFFICE:</td>
            <td class="saln-pdf-side-value">{{ $saln->spouse_info['agency'] ?? $saln->spouse_info['agency_office'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="3"><span class="saln-pdf-line">&nbsp;</span></td>
            <td class="saln-pdf-side-label">OFFICE ADDRESS:</td>
            <td class="saln-pdf-side-value">{{ $saln->spouse_info['office_address'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="3"><span class="saln-pdf-line">&nbsp;</span></td>
            <td></td>
            <td class="saln-pdf-side-value">&nbsp;</td>
        </tr>
    </table>

    <p class="saln-pdf-instruction">Spouses, who are both public officials or employees, may file the SALN jointly or separately.</p>
    <p class="saln-pdf-instruction">The declarant shall check the appropriate box</p>
    <table class="saln-pdf-choice-table">
        <tr>
            <td><span class="saln-pdf-box">{{ in_array($filingStatus, ['joint', 'joint filing'], true) ? 'X' : '' }}</span><em>Joint Filing</em></td>
            <td><span class="saln-pdf-box">{{ in_array($filingStatus, ['separate', 'separate filing'], true) ? 'X' : '' }}</span><em>Separate Filing</em></td>
            <td><span class="saln-pdf-box">{{ in_array($filingStatus, ['not_applicable', 'not applicable'], true) ? 'X' : '' }}</span><em>Not Applicable</em></td>
        </tr>
    </table>

    <p class="saln-pdf-instruction">If with multiple marriages, indicate name(s) of spouses, otherwise check the "Not Applicable" box.</p>
    <table class="saln-pdf-multiple">
        @if($multipleMarriagesNA)
            <tr>
                <td style="width:50%; padding-left:12mm;"><span class="saln-pdf-line">&nbsp;</span></td>
                <td style="text-align:center;"><span class="saln-pdf-box">X</span><em>Not Applicable</em></td>
            </tr>
            <tr><td style="padding-left:12mm;"><span class="saln-pdf-line">&nbsp;</span></td><td></td></tr>
        @else
            @foreach($multipleSpouses as $spouseName)
                <tr><td style="width:50%; padding-left:12mm;"><span class="saln-pdf-line">{{ $spouseName }}</span></td><td></td></tr>
            @endforeach
            @for($i = count($multipleSpouses); $i < 2; $i++)
                <tr><td style="padding-left:12mm;"><span class="saln-pdf-line">&nbsp;</span></td><td></td></tr>
            @endfor
        @endif
    </table>

    <div class="saln-pdf-double-rule"></div>

    <div class="saln-pdf-section-title saln-pdf-children-title underlined">Unmarried Children Below Eighteen (18) Years of Age Living in Declarant's Household</div>
    <table class="saln-pdf-children">
        <thead>
            <tr>
                <th style="width:72%;">NAME OF CHILD</th>
                <th>AGE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($childrenRows as $child)
                <tr>
                    <td><span class="saln-pdf-child-line">{{ $child['name'] ?? '' }}</span></td>
                    <td><span class="saln-pdf-child-line">{{ $child['age'] ?? '' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="saln-pdf-double-rule"></div>

    <div class="saln-pdf-section-title">Assets, Liabilities and Networth</div>
    <p class="saln-pdf-note">(Including those of the spouse and unmarried children below eighteen (18) years of age living in declarant's household)</p>

    <p class="saln-pdf-subheading">1. &nbsp; ASSETS</p>
    <p class="saln-pdf-subheading" style="margin-left:7mm;">a. Real Properties</p>
    <table class="saln-pdf-main-table">
        <colgroup>
            <col style="width:12%">
            <col style="width:12%">
            <col style="width:12%">
            <col style="width:13%">
            <col style="width:15%">
            <col style="width:7%">
            <col style="width:10%">
            <col style="width:19%">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">Description<br><span style="font-weight:normal; text-transform:none;">(e.g. Lot, House and Lot, Condominium and Improvements)</span></th>
                <th rowspan="2">Kind<br><span style="font-weight:normal; text-transform:none;">(e.g. Residential, Commercial, Industrial, Agricultural and Mixed Use)</span></th>
                <th rowspan="2">Exact<br>Location</th>
                <th rowspan="2">Assessed Value<br><span style="font-weight:normal; text-transform:none;">(As found in the Tax Declaration of Real Property)</span></th>
                <th rowspan="2">Current Fair Market Value<br><span style="font-weight:normal; text-transform:none;">(As found in the Tax Declaration of Real Property)</span></th>
                <th colspan="2">Acquisition</th>
                <th rowspan="2">Acquisition Cost</th>
            </tr>
            <tr>
                <th>Year</th>
                <th>Mode</th>
            </tr>
        </thead>
        <tbody>
            @foreach($realPropertyRows as $prop)
                <tr>
                    <td>{{ $prop['description'] ?? '' }}</td>
                    <td>{{ $prop['kind'] ?? '' }}</td>
                    <td>{{ $prop['location'] ?? $prop['exact_location'] ?? '' }}</td>
                    <td class="text-center">{{ isset($prop['assessed_value']) ? 'PHP '.number_format((float) $prop['assessed_value'], 2) : '' }}</td>
                    <td class="text-center">{{ isset($prop['fair_market_value']) || isset($prop['current_fair_market_value']) ? 'PHP '.number_format((float) ($prop['fair_market_value'] ?? $prop['current_fair_market_value'] ?? 0), 2) : '' }}</td>
                    <td class="text-center">{{ $prop['acquisition_year'] ?? '' }}</td>
                    <td class="text-center">{{ $prop['acquisition_mode'] ?? '' }}</td>
                    <td class="text-center font-bold">{{ isset($prop['acquisition_cost']) ? 'PHP '.number_format((float) $prop['acquisition_cost'], 2) : '' }}</td>
                </tr>
            @endforeach
            <tr class="saln-pdf-total-row">
                <td colspan="7" class="text-right">Subtotal:</td>
                <td class="text-center">PHP {{ number_format($realSubtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="saln-pdf-subheading" style="margin-left:7mm;">b. Personal Properties</p>
    <table class="saln-pdf-main-table">
        <colgroup>
            <col style="width:50%">
            <col style="width:22%">
            <col style="width:28%">
        </colgroup>
        <thead>
            <tr>
                <th>Description</th>
                <th>Acquisition Year</th>
                <th>Acquisition Cost / Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($personalPropertyRows as $prop)
                <tr>
                    <td>{{ $prop['description'] ?? '' }}</td>
                    <td class="text-center">{{ $prop['acquisition_year'] ?? $prop['year_acquired'] ?? '' }}</td>
                    <td class="text-center font-bold">{{ isset($prop['acquisition_cost']) ? 'PHP '.number_format((float) $prop['acquisition_cost'], 2) : '' }}</td>
                </tr>
            @endforeach
            <tr class="saln-pdf-total-row">
                <td colspan="2" class="text-right">Subtotal:</td>
                <td class="text-center">PHP {{ number_format($personalSubtotal, 2) }}</td>
            </tr>
            <tr class="saln-pdf-total-row">
                <td colspan="2" class="text-right">TOTAL ASSETS:</td>
                <td class="text-center">PHP {{ number_format($saln->total_assets, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="avoid-break" style="width:52mm; margin:6mm 0 0 auto; text-align:center;">
        <span class="saln-pdf-line">&nbsp;</span>
        <strong style="font-size:8pt;">Signature/Initial of Declarant</strong>
    </div>

    <div class="saln-pdf-page-break"></div>

    <p class="saln-pdf-subheading" style="font-size:13pt;">2. &nbsp; LIABILITIES</p>
    <table class="saln-pdf-main-table">
        <colgroup>
            <col style="width:38%">
            <col style="width:42%">
            <col style="width:20%">
        </colgroup>
        <thead>
            <tr>
                <th>Nature</th>
                <th>Name of Creditors</th>
                <th>Outstanding Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($liabilityRows as $liab)
                <tr>
                    <td>{{ $liab['nature'] ?? '' }}</td>
                    <td>{{ $liab['creditor'] ?? $liab['name_of_creditors'] ?? '' }}</td>
                    <td class="text-center font-bold">{{ isset($liab['outstanding_balance']) ? 'PHP '.number_format((float) $liab['outstanding_balance'], 2) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="saln-pdf-summary">
        <tr>
            <td class="text-right">TOTAL LIABILITIES: <span class="saln-pdf-heavy-line">PHP {{ number_format($saln->total_liabilities, 2) }}</span></td>
        </tr>
        <tr>
            <td class="text-right">NET WORTH: Total Assets less Total Liabilities = <span class="saln-pdf-heavy-line">PHP {{ number_format($saln->net_worth, 2) }}</span></td>
        </tr>
    </table>

    <div class="saln-pdf-section-title">Business Interests and Financial Connections</div>
    <p class="saln-pdf-note">(of Declarant / Declarant's spouse / Unmarried Children Below Eighteen (18) years of Age Living in Declarant's Household)</p>
    <p style="margin:0 0 2mm;"><span class="saln-pdf-box">{{ ! $saln->has_business_interests ? 'X' : '' }}</span>I/We do not have any business interest or financial connection.</p>
    <table class="saln-pdf-main-table">
        <thead>
            <tr>
                <th>Name of Entity/Business Enterprise</th>
                <th>Business Address</th>
                <th>Nature of Business Interest &amp;/or Financial Connection</th>
                <th>Date of Acquisition of Interest or Connection</th>
            </tr>
        </thead>
        <tbody>
            @foreach($businessRows as $biz)
                <tr>
                    <td>{{ $biz['name'] ?? '' }}</td>
                    <td>{{ $biz['address'] ?? '' }}</td>
                    <td>{{ $biz['nature'] ?? '' }}</td>
                    <td class="text-center">{{ isset($biz['acquisition_date']) ? \Carbon\Carbon::parse($biz['acquisition_date'])->format('M d, Y') : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="saln-pdf-section-title">Relatives in the Government Service</div>
    <p class="saln-pdf-note">(Within the Fourth Degree of Consanguinity or Affinity. Include also Bilas, Balae and Inso)</p>
    <p style="margin:0 0 2mm;"><span class="saln-pdf-box">{{ ! $saln->has_relatives_in_gov ? 'X' : '' }}</span>I/We do not know of any relative/s in the government service</p>
    <table class="saln-pdf-main-table">
        <thead>
            <tr>
                <th>Name of Relative</th>
                <th>Relationship</th>
                <th>Position</th>
                <th>Name of Agency/Office and Address</th>
            </tr>
        </thead>
        <tbody>
            @foreach($relativeRows as $rel)
                <tr>
                    <td>{{ $rel['name'] ?? '' }}</td>
                    <td>{{ $rel['relationship'] ?? '' }}</td>
                    <td>{{ $rel['position'] ?? '' }}</td>
                    <td>{{ $rel['agency'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="saln-pdf-cert">
        I hereby certify that these are true and correct statements of my assets, liabilities, net worth, business interests and financial connections, including those of my spouse and unmarried children below eighteen (18) years of age living in my household, and that to the best of my knowledge, the above-enumerated are names of my relatives in the government within the fourth civil degree of consanguinity or affinity.
    </p>
    <p class="saln-pdf-cert">
        I hereby authorize the Ombudsman or his/her duly authorized representative to obtain and secure from all appropriate government agencies, including the Bureau of Internal Revenue such documents that may show my assets, liabilities, net worth, business interests and financial connections, to include those of my spouse and unmarried children below 18 years of age living with me in my household covering previous years to include the year I first assumed office in government.
    </p>

    <p style="font-size:8pt; margin:0 0 3mm;">Date: <span style="display:inline-block; min-width:42mm; border-bottom:0.25mm solid #000; text-align:center;">{{ $dateAccomplishedText }}</span></p>

    <table class="saln-pdf-sign-grid">
        <tr>
            <td>
                <div class="saln-pdf-sign-line">
                    @if($declarantSignatureUrl)
                        <img src="{{ $declarantSignatureUrl }}" alt="Declarant signature">
                    @endif
                </div>
                <div class="saln-pdf-sign-label">Signature of Declarant</div>
                <table class="saln-pdf-id-row">
                    <tr><td style="width:35mm;"><strong>Government Issued ID:</strong></td><td><span class="saln-pdf-line">{{ $saln->declarant_info['government_issued_id'] ?? '' }}</span></td></tr>
                    <tr><td><strong>ID No.:</strong></td><td><span class="saln-pdf-line">{{ $saln->declarant_info['id_no'] ?? '' }}</span></td></tr>
                    <tr><td><strong>Date Issued:</strong></td><td><span class="saln-pdf-line">{{ $saln->declarant_info['date_issued'] ?? '' }}</span></td></tr>
                </table>
            </td>
            <td>
                <div class="saln-pdf-sign-line"></div>
                <div class="saln-pdf-sign-label">Signature of Declarant</div>
                <table class="saln-pdf-id-row">
                    <tr><td style="width:35mm;"><strong>Government Issued ID:</strong></td><td><span class="saln-pdf-line">{{ $saln->spouse_info['government_issued_id'] ?? '' }}</span></td></tr>
                    <tr><td><strong>ID No.:</strong></td><td><span class="saln-pdf-line">{{ $saln->spouse_info['id_no'] ?? '' }}</span></td></tr>
                    <tr><td><strong>Date Issued:</strong></td><td><span class="saln-pdf-line">{{ $saln->spouse_info['date_issued'] ?? '' }}</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="saln-pdf-oath">
        <p><strong>SUBSCRIBED AND SWORN</strong> to before me this _____ day of _____________, affiant exhibiting to me the above-stated government-issued identification card.</p>
        <div class="saln-pdf-oath-officer">
            <span class="saln-pdf-line">{{ $saln->declarant_info['person_administering_oath'] ?? '' }}</span>
            <div class="saln-pdf-oath-label">(Person Administering Oath)</div>
        </div>
    </div>

    <div class="saln-pdf-footnotes">
        <p><sup>i</sup> Position, Agency, and Address shall only be declared if the spouse is a public official or employee.</p>
        <p><sup>ii</sup> Additional sheets may be used by the declarant, if necessary.</p>
        <p><sup>iii</sup> Capital or paraphernal assets, and liabilities of the declarant's spouse, and properties of children below 18 years of age and living in the declarant's household shall be disclosed using the additional sheets provided.</p>
        <p><sup>iv</sup> Balae refers to the parent of one's son or daughter-in-law; Bilas refers to a brother-in-law's wife or sister-in-law's husband; Inso refers to the appellation for the wife of an elder brother or male cousin.</p>
    </div>
</div>
