                @php
                    $realProperties = collect($saln->real_properties ?? [])->values();
                    $personalProperties = collect($saln->personal_properties ?? [])->values();
                    $liabilities = collect($saln->liabilities ?? [])->values();
                    $businessInterests = collect($saln->business_interests ?? [])->values();
                    $relativesInGov = collect($saln->relatives_in_gov ?? [])->values();
                    $realSubtotal = $realProperties->sum(fn ($prop) => (float)($prop['acquisition_cost'] ?? 0));
                    $personalSubtotal = $personalProperties->sum(fn ($prop) => (float)($prop['acquisition_cost'] ?? 0));

                    $realOfficialRows = $realProperties;
                    $personalOfficialRows = $personalProperties;
                    $liabilityOfficialRows = $liabilities;
                    $businessOfficialRows = $businessInterests;
                    $relativeOfficialRows = $relativesInGov;
                    $filingType = strtolower(str_replace([' ', '-'], '_', (string) ($saln->type_of_filing ?? '')));
                    $isAssumptionFiling = in_array($filingType, ['assumption', 'assumption_of_office'], true);
                    $isAnnualFiling = in_array($filingType, ['annual', 'annual_filing'], true);
                    $isExitFiling = in_array($filingType, ['exit', 'exit_from_service'], true);
                @endphp

                <section class="saln-page saln-page-1">
                @include('saln.partials.official-meta')

                <div class="saln-top-identity">
                <p class="saln-compliance-title">Compliance For:</p>
                <div class="saln-compliance-row saln-compliance-rule">
                    <span class="saln-compliance-option">
                        @include('saln.partials.box', ['checked' => $isAssumptionFiling])
                        Assumption of office as of {{ $isAssumptionFiling ? $saln->as_of_date->format('F d, Y') : '_______________' }}
                    </span>
                    <span class="saln-compliance-option">
                        @include('saln.partials.box', ['checked' => $isAnnualFiling])
                        Annual filing as of December 31, {{ $isAnnualFiling ? $saln->as_of_date->format('Y') : '________' }}
                    </span>
                    <span class="saln-compliance-option">
                        @include('saln.partials.box', ['checked' => $isExitFiling])
                        Exit as of {{ $isExitFiling ? $saln->as_of_date->format('F d, Y') : '_______________' }}
                    </span>
                </div>

                {{-- Declarant & Spouse --}}
                <table class="mb-2 saln-name-table">
                    <colgroup>
                        <col style="width:12%">
                        <col style="width:14.7%">
                        <col style="width:18.3%">
                        <col style="width:8%">
                        <col style="width:2.4%">
                        <col style="width:17.6%">
                        <col style="width:27%">
                    </colgroup>
                    <tr>
                        <td rowspan="4" class="font-bold align-top saln-name-role">DECLARANT:</td>
                        <td rowspan="2" class="text-center saln-name-cell">
                            <span class="saln-value-wrap">{{ $saln->declarant_info['family_name'] ?? '' }}</span>
                            <span class="text-[8px]">(Family Name)</span>
                        </td>
                        <td rowspan="2" class="text-center saln-name-cell">
                            <span class="saln-value-wrap">{{ $saln->declarant_info['first_name'] ?? '' }}</span>
                            <span class="text-[8px]">(First Name)</span>
                        </td>
                        <td rowspan="2" class="text-center saln-name-cell">
                            <span class="saln-value-wrap">{{ $saln->declarant_info['middle_initial'] ?? '' }}</span>
                            <span class="text-[8px]">(M.I.)</span>
                        </td>
                        <td rowspan="4" class="saln-empty-side-cell">&nbsp;</td>
                        <td class="font-bold saln-name-label">POSITION:</td>
                        <td class="saln-name-value">{{ $saln->declarant_info['position'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold saln-name-label">AGENCY/OFFICE:</td>
                        <td class="saln-name-value">{{ $saln->declarant_info['agency'] ?? $saln->declarant_info['agency_office'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="saln-name-blank-line">&nbsp;</td>
                        <td class="font-bold saln-name-label">OFFICE ADDRESS:</td>
                        <td class="saln-name-value">{{ $saln->declarant_info['office_address'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="saln-name-blank-line">&nbsp;</td>
                        <td class="saln-name-label">&nbsp;</td>
                        <td class="saln-name-value">&nbsp;</td>
                    </tr>
                    <tr>
                        <td rowspan="4" class="font-bold align-top saln-name-role">SPOUSE:</td>
                        <td rowspan="2" class="text-center saln-name-cell">
                            <span class="saln-value-wrap">{{ $saln->spouse_info['family_name'] ?? '' }}</span>
                            <span class="text-[8px]">(Family Name)</span>
                        </td>
                        <td rowspan="2" class="text-center saln-name-cell">
                            <span class="saln-value-wrap">{{ $saln->spouse_info['first_name'] ?? '' }}</span>
                            <span class="text-[8px]">(First Name)</span>
                        </td>
                        <td rowspan="2" class="text-center saln-name-cell">
                            <span class="saln-value-wrap">{{ $saln->spouse_info['middle_initial'] ?? '' }}</span>
                            <span class="text-[8px]">(M.I.)</span>
                        </td>
                        <td rowspan="4" class="saln-empty-side-cell">&nbsp;</td>
                        <td class="font-bold saln-name-label">POSITION:</td>
                        <td class="saln-name-value">{{ $saln->spouse_info['position'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold saln-name-label">AGENCY/OFFICE:</td>
                        <td class="saln-name-value">{{ $saln->spouse_info['agency'] ?? $saln->spouse_info['agency_office'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="saln-name-blank-line">&nbsp;</td>
                        <td class="font-bold saln-name-label">OFFICE ADDRESS:</td>
                        <td class="saln-name-value">{{ $saln->spouse_info['office_address'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="saln-name-blank-line">&nbsp;</td>
                        <td class="saln-name-label">&nbsp;</td>
                        <td class="saln-name-value">&nbsp;</td>
                    </tr>
                </table>

                @php
                    $hasSavedSaln = $hasSavedSaln ?? $saln->exists;
                    $multipleSpouses = collect($saln->declarant_info['multiple_spouses'] ?? [])
                        ->filter(fn ($spouseName) => trim((string) $spouseName) !== '')
                        ->values();
                    $multipleMarriagesNA = $hasSavedSaln
                        && ($saln->declarant_info['multiple_marriages_not_applicable'] ?? null) !== false
                        && $multipleSpouses->isEmpty();
                @endphp
                <p class="saln-instruction-label">Spouses, who are both public officials or employees, may file the SALN jointly or separately.</p>
                <p class="saln-instruction-label">The declarant shall check the appropriate box</p>
                <div class="saln-checkbox-row mb-3">
                    <span class="saln-check-label">@include('saln.partials.box', ['checked' => strtolower($saln->filing_status) === 'joint' || $saln->filing_status === 'Joint Filing']) Joint Filing</span>
                    <span class="saln-check-label">@include('saln.partials.box', ['checked' => strtolower($saln->filing_status) === 'separate' || $saln->filing_status === 'Separate Filing']) Separate Filing</span>
                    <span class="saln-check-label">@include('saln.partials.box', ['checked' => strtolower($saln->filing_status) === 'not_applicable' || $saln->filing_status === 'Not Applicable']) Not Applicable</span>
                </div>

                <p class="saln-instruction-label">If with multiple marriages, indicate name(s) of spouses, otherwise check the "Not Applicable" box.</p>
                <div class="mb-4 saln-multiple-marriage-section">
                    <div class="saln-multiple-marriage-row">
                        <span class="saln-line flex-1">{{ $multipleMarriagesNA ? '' : ($multipleSpouses[0] ?? '') }}</span>
                        <span class="saln-check-label mt-1">@include('saln.partials.box', ['checked' => $multipleMarriagesNA]) Not Applicable</span>
                    </div>
                    @foreach($multipleSpouses->slice(1) as $spouseName)
                        <div class="saln-line">{{ $spouseName }}</div>
                    @endforeach
                    @for($i = max($multipleSpouses->count(), 1); $i < 2; $i++)
                        <div class="saln-line">&nbsp;</div>
                    @endfor
                </div>
                </div>

                {{-- Children --}}
                <div class="saln-section-title">Unmarried Children Below Eighteen (18) Years of Age Living in Declarant's Household</div>
                <table class="mb-4 saln-children-table">
                    <colgroup>
                        <col style="width:72%">
                        <col style="width:28%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>NAME OF CHILD</th>
                            <th>AGE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($saln->children ?? [] as $child)
                            <tr>
                                <td class="text-center"><span class="saln-child-line">{{ $child['name'] ?? '' }}</span></td>
                                <td class="text-center"><span class="saln-child-line">{{ $child['age'] ?? '' }}</span></td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 4; $i++)
                                <tr>
                                    <td><span class="saln-child-line">&nbsp;</span></td>
                                    <td><span class="saln-child-line">&nbsp;</span></td>
                                </tr>
                            @endfor
                        @endforelse
                    </tbody>
                </table>

                {{-- Assets header --}}
                <div class="saln-section-title saln-assets-title">Assets, Liabilities and Networth</div>
                <p class="saln-note text-center mb-3">(Including those of the spouse and unmarried children below eighteen (18) years of age living in declarant's household)</p>

                <p class="font-bold mb-1">1. ASSETS</p>
                <p class="font-bold mb-1 ml-3">a. Real Properties</p>
                <table class="mb-1 saln-wide-table saln-asset-table">
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
                            <th class="saln-th" rowspan="2">DESCRIPTION<br><span class="saln-th-sm">(e.g. Lot, House and Lot, Condominium and Improvements)</span></th>
                            <th class="saln-th" rowspan="2">KIND<br><span class="saln-th-sm">(e.g. Residential, Commercial, Industrial, Agricultural and Mixed Use)</span></th>
                            <th class="saln-th" rowspan="2">EXACT<br>LOCATION</th>
                            <th class="saln-th" rowspan="2">ASSESSED VALUE<br><span class="saln-th-sm">(As found in the Tax Declaration of Real Property)</span></th>
                            <th class="saln-th" rowspan="2">CURRENT FAIR MARKET VALUE<br><span class="saln-th-sm">(As found in the Tax Declaration of Real Property)</span></th>
                            <th class="saln-th" colspan="2">ACQUISITION</th>
                            <th class="saln-th" rowspan="2">ACQUISITION COST</th>
                        </tr>
                        <tr>
                            <th class="saln-th">YEAR</th>
                            <th class="saln-th">MODE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($realOfficialRows as $prop)
                            <tr class="text-center">
                                <td>{{ $prop['description'] ?? '' }}</td>
                                <td>{{ $prop['kind'] ?? '' }}</td>
                                <td>{{ $prop['location'] ?? $prop['exact_location'] ?? '' }}</td>
                                <td>{{ isset($prop['assessed_value']) ? 'PHP '.number_format((float)$prop['assessed_value'], 2) : '' }}</td>
                                <td>{{ isset($prop['fair_market_value']) || isset($prop['current_fair_market_value']) ? 'PHP '.number_format((float)($prop['fair_market_value'] ?? $prop['current_fair_market_value'] ?? 0), 2) : '' }}</td>
                                <td>{{ $prop['acquisition_year'] ?? '' }}</td>
                                <td>{{ $prop['acquisition_mode'] ?? '' }}</td>
                                <td class="font-bold">{{ isset($prop['acquisition_cost']) ? 'PHP '.number_format((float)$prop['acquisition_cost'], 2) : '' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 5; $i++)
                                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                            @endfor
                        @endforelse
                        <tr class="saln-total-row">
                            <td colspan="7" class="text-right font-bold">Subtotal:</td>
                            <td class="text-center font-bold">PHP {{ number_format($realSubtotal, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <p class="font-bold mb-1 ml-3 mt-3">b. Personal Properties</p>
                <table class="mb-1 saln-text-table saln-asset-table">
                    <thead>
                        <tr>
                            <th class="saln-th w-1/2">DESCRIPTION</th>
                            <th class="saln-th">ACQUISITION YEAR</th>
                            <th class="saln-th">ACQUISITION COST / AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($personalOfficialRows as $prop)
                            <tr class="text-center">
                                <td>{{ $prop['description'] ?? '' }}</td>
                                <td>{{ $prop['acquisition_year'] ?? $prop['year_acquired'] ?? '' }}</td>
                                <td class="font-bold">{{ isset($prop['acquisition_cost']) ? 'PHP '.number_format((float)$prop['acquisition_cost'], 2) : '' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 6; $i++)
                                <tr><td>&nbsp;</td><td></td><td></td></tr>
                            @endfor
                        @endforelse
                        <tr class="saln-total-row">
                            <td colspan="2" class="text-right font-bold">Subtotal:</td>
                            <td class="text-center font-bold">PHP {{ number_format($personalSubtotal, 2) }}</td>
                        </tr>
                        <tr class="saln-total-row">
                            <td colspan="2" class="text-right font-bold text-[11px]">TOTAL ASSETS:</td>
                            <td class="text-center font-bold text-[11px]">PHP {{ number_format($saln->total_assets, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="saln-page1-signature text-center">
                    <div class="border-b border-black min-h-[28px] mb-1"></div>
                    <div class="font-bold">Signature/Initial of Declarant</div>
                </div>
                <div class="saln-page-footer" aria-hidden="true">Page 1 of 2</div>
                </section>

                <section class="saln-page saln-page-2">
                <p class="saln-liabilities-heading">2. &nbsp; LIABILITIES</p>
                <table class="saln-liabilities-table">
                    <colgroup>
                        <col style="width:36%">
                        <col style="width:40%">
                        <col style="width:24%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="saln-th">NATURE</th>
                            <th class="saln-th">NAME OF CREDITORS</th>
                            <th class="saln-th">OUTSTANDING BALANCE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($liabilityOfficialRows as $liab)
                            <tr class="text-center">
                                <td>{{ $liab['nature'] ?? '' }}</td>
                                <td>{{ $liab['creditor'] ?? $liab['name_of_creditors'] ?? '' }}</td>
                                <td class="font-bold">{{ isset($liab['outstanding_balance']) ? 'PHP '.number_format((float)$liab['outstanding_balance'], 2) : '' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 3; $i++)
                                <tr><td>&nbsp;</td><td></td><td></td></tr>
                            @endfor
                        @endforelse
                    </tbody>
                </table>

                <div class="saln-liabilities-summary">
                    <div class="saln-total-liabilities-row">
                        <span class="saln-total-liabilities-label">TOTAL LIABILITIES:</span>
                        <span class="saln-total-liabilities-value">PHP {{ number_format($saln->total_liabilities, 2) }}</span>
                    </div>
                    <div class="saln-networth-row">
                        <span>NET WORTH: Total Assets less Total Liabilities =</span>
                        <span class="saln-networth-value">PHP {{ number_format($saln->net_worth, 2) }}</span>
                    </div>
                </div>

                {{-- Business Interests --}}
                <div class="saln-section-title border-t border-black pt-3">Business Interests and Financial Connections</div>
                <p class="saln-note text-center mb-2">(of Declarant / Declarant's spouse / Unmarried Children Below Eighteen (18) years of Age Living in Declarant's Household)</p>
                <p class="mb-2">
                    <span class="saln-check-label">
                        @include('saln.partials.box', ['checked' => $hasSavedSaln && ! $saln->has_business_interests])
                        I/We do not have any business interest or financial connection.
                    </span>
                </p>
                <table class="mb-4 saln-text-table">
                    <thead>
                        <tr>
                            <th class="saln-th">NAME OF ENTITY/BUSINESS ENTERPRISE</th>
                            <th class="saln-th">BUSINESS ADDRESS</th>
                            <th class="saln-th">NATURE OF BUSINESS INTEREST &amp;/OR FINANCIAL CONNECTION</th>
                            <th class="saln-th">DATE OF ACQUISITION OF INTEREST OR CONNECTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($businessOfficialRows as $biz)
                            <tr class="text-center text-[10px]">
                                <td>{{ $biz['name'] ?? '' }}</td>
                                <td>{{ $biz['address'] ?? '' }}</td>
                                <td>{{ $biz['nature'] ?? '' }}</td>
                                <td>{{ isset($biz['acquisition_date']) ? \Carbon\Carbon::parse($biz['acquisition_date'])->format('M d, Y') : '' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 3; $i++)
                                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                            @endfor
                        @endforelse
                    </tbody>
                </table>

                {{-- Relatives --}}
                <div class="saln-section-title">Relatives in the Government Service</div>
                <p class="saln-note text-center mb-2">(Within the Fourth Degree of Consanguinity or Affinity. Include also Bilas, Balae and Inso)</p>
                <p class="mb-2">
                    <span class="saln-check-label">
                        @include('saln.partials.box', ['checked' => $hasSavedSaln && ! $saln->has_relatives_in_gov])
                        I/We do not know of any relative/s in the government service
                    </span>
                </p>
                <table class="mb-4 saln-text-table">
                    <thead>
                        <tr>
                            <th class="saln-th">NAME OF RELATIVE</th>
                            <th class="saln-th">RELATIONSHIP</th>
                            <th class="saln-th">POSITION</th>
                            <th class="saln-th">NAME OF AGENCY/OFFICE AND ADDRESS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($relativeOfficialRows as $rel)
                            <tr class="text-center text-[10px]">
                                <td>{{ $rel['name'] ?? '' }}</td>
                                <td>{{ $rel['relationship'] ?? '' }}</td>
                                <td>{{ $rel['position'] ?? '' }}</td>
                                <td>{{ $rel['agency'] ?? '' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 4; $i++)
                                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                            @endfor
                        @endforelse
                    </tbody>
                </table>

                {{-- Certification --}}
                <p class="saln-cert">
                    I hereby certify that these are true and correct statements of my assets, liabilities, net worth, business interests and financial connections, including those of my spouse and unmarried children below eighteen (18) years of age living in my household, and that to the best of my knowledge, the above-enumerated are names of my relatives in the government within the fourth civil degree of consanguinity or affinity.
                </p>
                <p class="saln-cert">
                    I hereby authorize the Ombudsman or his/her duly authorized representative to obtain and secure from all appropriate government agencies, including the Bureau of Internal Revenue such documents that may show my assets, liabilities, net worth, business interests and financial connections, to include those of my spouse and unmarried children below 18 years of age living with me in my household covering previous years to include the year I first assumed office in government.
                </p>

                @php
                    $dateAccomplished = $saln->declarant_info['date_accomplished'] ?? null;
                    $dateAccomplishedText = $dateAccomplished
                        ? \Carbon\Carbon::parse($dateAccomplished)->format('F d, Y')
                        : '';
                    $declarantGovId = $saln->declarant_info['government_issued_id'] ?? '';
                    $declarantIdNo = $saln->declarant_info['id_no'] ?? '';
                    $declarantDateIssued = $saln->declarant_info['date_issued'] ?? '';
                    $spouseGovId = $saln->spouse_info['government_issued_id'] ?? '';
                    $spouseIdNo = $saln->spouse_info['id_no'] ?? '';
                    $spouseDateIssued = $saln->spouse_info['date_issued'] ?? '';
                    $oathOfficer = $saln->declarant_info['person_administering_oath'] ?? '';
                    $declarantSignatureUrl = $saln->employee?->effective_signature_url;
                @endphp

                <p class="text-[10px] mb-4">Date: <span class="inline-block border-b border-black min-w-[180px] text-center">{{ $dateAccomplishedText }}</span></p>

                {{-- Signatures --}}
                <div class="saln-signature-grid">
                    <div>
                        <div class="saln-sign-line">
                            @if($declarantSignatureUrl)
                                <img src="{{ $declarantSignatureUrl }}" alt="Declarant signature">
                            @endif
                        </div>
                        <div class="text-center font-bold">Signature of Declarant</div>
                        <div class="mt-3 space-y-1">
                            <div class="flex gap-2"><span class="font-bold w-32">Government Issued ID:</span><span class="saln-line flex-1">{{ $declarantGovId }}</span></div>
                            <div class="flex gap-2"><span class="font-bold w-32">ID No.:</span><span class="saln-line flex-1">{{ $declarantIdNo }}</span></div>
                            <div class="flex gap-2"><span class="font-bold w-32">Date Issued:</span><span class="saln-line flex-1">{{ $declarantDateIssued }}</span></div>
                        </div>
                    </div>
                    <div>
                        <div class="saln-sign-line"></div>
                        <div class="text-center font-bold">Signature of Declarant</div>
                        <div class="mt-3 space-y-1">
                            <div class="flex gap-2"><span class="font-bold w-32">Government Issued ID:</span><span class="saln-line flex-1">{{ $spouseGovId }}</span></div>
                            <div class="flex gap-2"><span class="font-bold w-32">ID No.:</span><span class="saln-line flex-1">{{ $spouseIdNo }}</span></div>
                            <div class="flex gap-2"><span class="font-bold w-32">Date Issued:</span><span class="saln-line flex-1">{{ $spouseDateIssued }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="saln-oath-row">
                    <p class="saln-oath-text">
                        <span class="font-bold">SUBSCRIBED AND SWORN</span> to before me this _____ day of _____________, affiant exhibiting to me the above-stated government-issued identification card.
                    </p>
                    <div class="saln-oath-officer">
                        <div class="saln-oath-officer-line">{{ $oathOfficer }}</div>
                        <div class="saln-oath-label">(Person Administering Oath)</div>
                    </div>
                </div>
                <div class="saln-footnotes">
                    <p><sup>i</sup> Position, Agency, and Address shall only be declared if the spouse is a public official or employee.</p>
                    <p><sup>ii</sup> Additional sheets may be used by the declarant, if necessary.</p>
                    <p><sup>iii</sup> Capital or paraphernal assets, and liabilities of the declarant's spouse, and properties of children below 18 years of age and living in the declarant's household shall be disclosed using the additional sheets provided.</p>
                    <p><sup>iv</sup> Balae refers to the parent of one's son or daughter-in-law; Bilas refers to a brother-in-law's wife or sister-in-law's husband; Inso refers to the appellation for the wife of an elder brother or male cousin.</p>
                </div>
                <div class="saln-page-footer" aria-hidden="true">Page 2 of 2</div>
                </section>
