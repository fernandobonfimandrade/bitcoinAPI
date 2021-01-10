<?php

namespace App\Services;

use Mailjet\Resources;

class Mailling
{

    private $mj, $body, $response;

    public function __construct()
    {
        $this->mj = new \Mailjet\Client(env('MAILJET_KEY'),env('MAILJET_SECRET'),true,['version' => 'v3.1']);
    }

    public function setBody($mailTo, $nameTo, $subject, $text){
        try {
        $this->body = [
            'Messages' => [
              [
                'From' => [
                  'Email' => "fernandobonfimandrade@gmail.com",
                  'Name' => "Fernando"
                ],
                'To' => [
                  [
                    'Email' => $mailTo,
                    'Name' => $nameTo
                  ]
                ],
                'Subject' =>  $subject,
                'HTMLPart' => $text,
                'CustomID' => "AppGettingStartedTest"
              ]
            ]
          ];
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function send(){
        try {
            $response = $this->mj->post(Resources::$Email, ['body' => $this->body]);
            return $response->success();
        } catch (\Exception $e) {
            dd($e);
        }
    }

}