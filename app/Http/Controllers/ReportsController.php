<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\User;
use App\Appointment;
use App\Role;

class ReportsController extends Controller
{
    /**
     * 
     * 
     * 
     */
    public function index() {
        return view('pages.reports.index');
    }

    /**
     * Generate the required Data for the 
     * 
     * 
     * @param request
     */
    public function show(Request $request) {
        $selectedMonth = (string)$request->input('month');
        $selectedYear = (string)$request->input('year');
        $selectedDay = (int)$request->input('day');
        $isAgentMeetingDateSet = (boolean)$request->input('isAgentMeetingDateSet'); 
        $isAppointmentWon = (boolean)$request->input('isAppointmentWon');

        /**
         * ttips for making this fuckign controller much much 
         * cleaner
         * using Local Scopes
         * Apply DRY principle
         */
        // 
        $appointmentClosingCommentStatuses = ['open', 'positive', 'negative', 'not_home', 'processing', 'multi_year_contract', 'wollte k.t'];
        // $appointmentClosingCommentStatuses = ['offen', 'positiv', 'negativ', 'Nicht zu Hause', 'Behandlung', 'MJV', 'Wollte k.T'];
        $statusDe = [
            'open' => 'offen',
            'positive' => 'positiv',
            'negative' => 'negativ',
            'not_home' => 'Nicht zu Hause',
            'processing' => 'Behandlung',
            'multi_year_contract' => 'MJV',
            'wollte k.t' => 'Wollte k.T'
        ];

        // Appoitments for the selected year/month
        $allAppointments = Appointment::SelectedMonth($selectedYear, $selectedMonth)
                            ->meetingDate($isAgentMeetingDateSet)
                            ->get();
        $numOfAllApointments = count($allAppointments);

        // Number of appointments per user 
        $numOfAppointmentsPerSalesAgent = [];
        $numOfAppointmentsPerCallAgent = [];
        $salesAgentsWithAppointments = User::with(['appointments' => function ($query) use ($selectedYear, $selectedMonth, $isAgentMeetingDateSet) {
                            $query->SelectedMonth($selectedYear, $selectedMonth)
                            ->meetingDate($isAgentMeetingDateSet);
                        }])->get();


        $callAgentsWithAppointments = User::with(['callAgentsAppointments' => function ($query) use ($selectedYear, $selectedMonth, $isAgentMeetingDateSet) {
                                        $query->SelectedMonth($selectedYear, $selectedMonth)
                                            ->meetingDate($isAgentMeetingDateSet);
                                    }])->get();;

        foreach ($salesAgentsWithAppointments as $key => $user) {
            if(strtolower($user->role->name) == 'sales_agent') {
                $numOfAppointmentsPerSalesAgent[$user->user_name]['total'] = count($user->appointments);
                $numOfAppointmentsPerSalesAgent[$user->user_name]['won'] = 0;
                $numOfAppointmentsPerSalesAgent[$user->user_name]['name'] = $user->user_name;

                foreach ($user->appointments as $key => $appointment) {
                    if (isset($appointment->graduation_abschluss)) {
                        $numOfAppointmentsPerSalesAgent[$user->user_name]['won']++;
                    }
                }
            }
        };

        foreach ($callAgentsWithAppointments as $key => $user) {
            if (strtolower($user->role->name) == 'call_agent') {
                $numOfAppointmentsPerCallAgent[$user->user_name]['total'] = count($user->callAgentsAppointments);
                $numOfAppointmentsPerCallAgent[$user->user_name]['name'] = $user->user_name;
            }
        }

        // Number of appointments per day
        $numOfAppointmentsPerDay = [];

        $numOfAllApointmentsPerDayPositive = [];
        $numOfAllApointmentsPerDayNegative = [];
        
        $numberOfAppointmentsWonPerDay = [];
        $numberOfAppointmentsNotWonPerDay = [];

        $numOfAppointmentsPerStatus = [];
        foreach ($appointmentClosingCommentStatuses as $key => $status) {
            $numOfAppointmentsPerStatus[$statusDe[strtolower($status)]] = 0;
        }

        if(now()->month == $selectedMonth) {
            $dayToUse = $selectedDay;
        } else {
            $dayToUse = \Carbon\Carbon::createFromDate($selectedYear, $selectedMonth)->daysInMonth;
        }

        while ($dayToUse > 0) {
            $allAppointmentsPositive = [];
            $allAppointmentsNegative = [];

            $numberOfAppointmentsNotWonPerDay[$dayToUse] = 0;
            $numberOfAppointmentsWonPerDay[$dayToUse] = 0;

            $allAppointments = Appointment::SelectedMonth($selectedYear, $selectedMonth)
                            ->meetingDate($isAgentMeetingDateSet)
                            ->whereDay('created_at', $dayToUse)
                            ->appointmentWon($isAppointmentWon)
                            ->get();
            
            $numOfAppointmentsPerDay[$dayToUse] = count($allAppointments);

            foreach($allAppointments as $key => $appointment) {
                if( strtolower($appointment->comment_status) == 'positive') {
                    array_push($allAppointmentsPositive, $appointment);
                };
                if (strtolower($appointment->comment_status) == 'negative') {
                    array_push($allAppointmentsNegative, $appointment);
                };
                // won or not won
                if ($appointment->graduation_abschluss == null) {
                    $numberOfAppointmentsNotWonPerDay[$dayToUse]++;
                };
                if($appointment->graduation_abschluss != null) {
                    $numberOfAppointmentsWonPerDay[$dayToUse]++;
                };

                // Num of appointment per status 
                foreach ($appointmentClosingCommentStatuses as $key => $status) {
                    if( strtolower($appointment->comment_status) == strtolower($status) ) {
                        if(isset($statusDe[strtolower($status)])) {
                            $numOfAppointmentsPerStatus[$statusDe[strtolower($status)]]++;
                        }
                    }
                }
            }

            $numOfAllApointmentsPerDayPositive[$dayToUse] = count($allAppointmentsPositive);
            $numOfAllApointmentsPerDayNegative[$dayToUse] = count($allAppointmentsNegative);
            
            $dayToUse = $dayToUse - 1;
        }

        // call centers
        $callAgentRoleId = Role::where('name', 'call_agent')->id;
        $callAgents = User::where('role_id', $callAgentRoleId)->with('callAgentsAppointments')->all();

        // Returning the result
        return response()->json([
            'numOfAppointmentsPerSalesAgent' => $numOfAppointmentsPerSalesAgent,
            'numOfAppointmentsPerCallAgent' => $numOfAppointmentsPerCallAgent,
            'numOfAllApointments' => $numOfAllApointments,
            'numOfAppointmentsPerDay' => $numOfAppointmentsPerDay,
            'numOfAppointmentsPerStatus' => $numOfAppointmentsPerStatus,
            'numOfAllApointmentsPerDayPositive' => $numOfAllApointmentsPerDayPositive,
            'numOfAllApointmentsPerDayNegative' => $numOfAllApointmentsPerDayNegative,
            'numberOfAppointmentsWonPerDay' => $numberOfAppointmentsWonPerDay,
            'numberOfAppointmentsNotWonPerDay' => $numberOfAppointmentsNotWonPerDay,
            'callAgents' => $callAgents
        ]);
    }
}
