<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Seed the leave types based on Philippine government leave benefits.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Vacation Leave',
                'description' => 'Annual leave granted to government employees to afford them the opportunity to rest, relax, and attend to personal matters. Employees earn 1.25 days of vacation leave credits per month (15 days per year). Unused leave credits are cumulative and may be monetized upon separation from service.',
                'legal_basis' => 'Sec. 51, Rule XVI of the Omnibus Rules Implementing Executive Order No. 292',
                'days_per_year' => 15,
            ],
            [
                'name' => 'Mandatory/Force Leave',
                'description' => 'Government employees with 10 or more days of vacation leave credits are required to go on a mandatory/forced leave of 5 working days annually. This is drawn from their accumulated vacation leave credits and must be taken to ensure the well-being and work-life balance of employees.',
                'legal_basis' => 'Sec. 25, Rule XVI of the Omnibus Rules Implementing Executive Order No. 292',
                'days_per_year' => 5,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Leave granted to employees on account of illness, injury, or any medical condition that prevents them from performing their duties. Employees earn 1.25 days of sick leave credits per month (15 days per year). Applications exceeding 5 successive days require a medical certificate. Unused sick leave credits are cumulative and may be monetized upon retirement.',
                'legal_basis' => 'Sec. 43, Rule XVI of the Omnibus Rules Implementing Executive Order No. 292',
                'days_per_year' => 15,
            ],
            [
                'name' => 'Maternity Leave',
                'description' => 'Leave granted to female employees who are pregnant, whether for normal delivery, caesarean section, miscarriage, or emergency termination of pregnancy. Under the Expanded Maternity Leave Law, qualified employees are entitled to 105 days of paid leave for live childbirth, with an option to extend for an additional 30 days without pay. Solo mothers receive an additional 15 days. Applies to every instance of pregnancy regardless of frequency.',
                'legal_basis' => 'Sec. 3 of Republic Act No. 11210 (Expanded Maternity Leave Law)',
                'days_per_year' => 105,
            ],
            [
                'name' => 'Paternity Leave',
                'description' => 'Leave granted to married male employees to allow them to lend support to their wife during her period of recovery and/or in caring for the newborn child. This benefit is available for the first four (4) deliveries of the legitimate spouse. The leave must be availed within a reasonable period from the date of delivery.',
                'legal_basis' => 'Sec. 2 of Republic Act No. 8187 (Paternity Leave Act of 1996)',
                'days_per_year' => 7,
            ],
            [
                'name' => 'Special Privilege Leave',
                'description' => 'Leave granted to government employees for personal milestones, important transactions, and parental obligations. This includes but is not limited to: birthday celebrations, wedding anniversaries, enrollment of children, attendance at school programs, hospitalization of immediate family members, and other analogous circumstances. This leave is non-cumulative and non-convertible to cash.',
                'legal_basis' => 'Sec. 21, Rule XVI of the Omnibus Rules Implementing Executive Order No. 292',
                'days_per_year' => 3,
            ],
            [
                'name' => 'Solo Parent Leave',
                'description' => 'Additional leave granted to employees who qualify as solo parents under the Solo Parents\' Welfare Act. A solo parent is any individual who falls under any of the following categories: those left solo due to death, detention, or abandonment of the spouse; unmarried mothers/fathers; or any other person who solely provides parental care. Must have rendered at least 1 year of service and must have notified the employer of solo parent status.',
                'legal_basis' => 'Sec. 8 of Republic Act No. 8972 (Solo Parents\' Welfare Act of 2000)',
                'days_per_year' => 7,
            ],
            [
                'name' => 'Study Leave',
                'description' => 'Leave granted to qualified employees to help them prepare for bar/board examinations or to complete a master\'s or doctoral degree. The study leave period shall not exceed 6 months and is non-cumulative. The employee must have rendered at least 2 years of continuous service, must have a satisfactory performance rating, and must enter into a contract to serve the agency for at least 2 years after the study leave.',
                'legal_basis' => 'Sec. 68, Rule XVI of the Omnibus Rules Implementing Executive Order No. 292',
                'days_per_year' => 150,
            ],
            [
                'name' => 'VAWC Leave',
                'description' => 'Leave granted to female employees who are victims of violence against women and their children (VAWC) as defined under RA 9262. This leave is extended to allow the employee to attend to medical and legal concerns. The leave can be availed in a continuous or intermittent manner within 1 year from filing and shall be covered by a protection order from the barangay or the court.',
                'legal_basis' => 'Sec. 43 of Republic Act No. 9262 (Anti-Violence Against Women and Their Children Act of 2004)',
                'days_per_year' => 10,
            ],
            [
                'name' => 'Rehabilitation Leave',
                'description' => 'Leave granted to employees who sustained wounds or injuries while in the performance of official duties. The leave allows employees to undergo medical treatment and rehabilitation to recover from work-related injuries. It may be granted for a maximum period as recommended by a competent physician and approved by the agency head. A medical certificate is required, and the leave is separate from sick leave credits.',
                'legal_basis' => 'Sec. 55, Rule XVI of the Omnibus Rules Implementing Executive Order No. 292 and CSC Resolution No. 021420',
                'days_per_year' => 15,
            ],
            [
                'name' => 'Special Leave Benefits for Women',
                'description' => 'Leave granted to female employees who undergo surgery due to gynecological disorders, regardless of tenure. This benefit covers procedures for conditions such as but not limited to: hysterectomy, ovariectomy, and mastectomy. The leave may be availed for a maximum of 2 months with full pay based on the employee\'s gross monthly compensation. A medical certificate is required to support the application.',
                'legal_basis' => 'Sec. 18 of Republic Act No. 9710 (Magna Carta of Women)',
                'days_per_year' => 60,
            ],
            [
                'name' => 'Special Emergency (Calamity) Leave',
                'description' => 'Leave granted to employees who were directly affected by natural calamities and/or disasters such as typhoons, floods, earthquakes, volcanic eruptions, and other analogous events. The employee must be residing or assigned in the area declared under a state of calamity. This leave is non-cumulative and non-convertible to cash. The employee should have rendered government service for at least 1 year.',
                'legal_basis' => 'CSC Resolution No. 1101502',
                'days_per_year' => 5,
            ],
            [
                'name' => 'Adoption Leave',
                'description' => 'Leave granted to employees who have legally adopted a child below 7 years of age as certified by the Department of Social Welfare and Development (DSWD). This leave allows the adoptive parent time to bond with and care for the newly adopted child. The leave must be availed within the period of adoption proceedings or upon the actual physical custody of the child.',
                'legal_basis' => 'Sec. 22 of Republic Act No. 8552 (Domestic Adoption Act of 1998)',
                'days_per_year' => 7,
            ],
            [
                'name' => 'Wellness Leave',
                'description' => 'Leave intended for mental health care, physical wellness activities, or a general break from work. It is separate from Vacation Leave, Sick Leave, and Special Privilege Leave; non-cumulative, non-commutable, and forfeited if unused within the calendar year.',
                'legal_basis' => 'CSC Memorandum Circular No. 01, s. 2026 (Wellness Leave Policy)',
                'days_per_year' => 5,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['name' => $leaveType['name']],
                $leaveType
            );
        }
    }
}
