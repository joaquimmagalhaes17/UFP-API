<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class APIController extends Controller {    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Schedule $schedule) {
        $this->schedule = $schedule;
    }

    public function index() {
        return json_encode(["Version" => "1.0"]);
    }

    public function getSchedule() {
        $tokenData = (object) $this->decryptToken($this->apiToken);

        if($tokenData->token) {
            if(!$this->hasUserScheduleDetails($tokenData->number)) {
                $scheduleAux = $this->getDataFromSOAPServer("schedule", array("schedule" => array("token" => $tokenData->token)));

                if(property_exists(json_decode($scheduleAux->scheduleResult), "Error"))
                    return $this->encodeMessage(1, "Invalid token");

                $parsedSchedule = $this->parseSchedule(json_decode($scheduleAux->scheduleResult)->schedule);

                if (!empty($parsedSchedule)) {
                    $userParsedSchedule = new $this->schedule;

                    $userParsedSchedule->number = $tokenData->number;
                    $userParsedSchedule->schedule = $parsedSchedule;

                    $userParsedSchedule->save();

                    return $this->encodeMessage(0, $userParsedSchedule->schedule);
                }

                return $this->encodeMessage(1, "No schedule information found");   
            }
            
            return $this->encodeMessage(0, $this->schedule->where("number", "=", $tokenData->number)->first()->schedule);
        }
        
        return $this->encodeMessage(1, "Not a valid token");
    }

    private function hasUserScheduleDetails($userNumber) {
        return $this->schedule->where("number", "=", $userNumber)->exists();
    }

    private function parseSchedule($schedule) {
        $scheduleAux = array();

        foreach($schedule as $days) {
            $scheduleAux[$days->Data][] = array("inicio" => $days->Inicio, "termo" => $days->Termo, "sala" => $days->Sala, "unidade" => $days->Unidade, "tipo" => $days->Tipo);
        }

        return $scheduleAux;
    }
}
