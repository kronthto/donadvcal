<?php

namespace App;

use Cekurte\Environment\Environment;
use PHPMailer;

class Notifier
{
    /** @var PHPMailer */
    protected $prototype;

    /**
     * Notifier constructor.
     */
    public function __construct()
    {
        $mailPrototype = new \PHPMailer();
        $mailPrototype->CharSet = 'UTF-8';
        $mailPrototype->setFrom(Environment::get('SENDER'));
        if (Environment::get('USE_SMTP', false)) {
            $mailPrototype->isSMTP();
            foreach (Environment::get('SMTP_PARAMS', []) as $paramKey => $value) {
                $mailPrototype->$paramKey = $value;
            }
        }

        $this->prototype = $mailPrototype;
    }

    /**
     * Returns a PHPMailer instance with some basic instance settings.
     *
     * @return \PHPMailer
     */
    protected function scaffoldMail()
    {
        return clone $this->prototype;
    }

    /**
     * Sends result-mail.
     *
     * @param array|int[] $winNumbers
     * @param string      $date
     *
     * @throws \Exception
     */
    public function notifyCalendars(array $winNumbers, $date)
    {
        $mail = $this->scaffoldMail();
        $recipients = explode(',', Environment::get('RECIPIENTS'));
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        $ownNumbers = array_map(function ($value) {
            return (int) $value;
        }, explode(',', Environment::get('NUMBERS')));
        $win = array_intersect($winNumbers, $ownNumbers);

        $mail->Subject = ((count($win) > 0) ? 'Win - ' : '').'DON Adv Cal Results '.$date;

        $mail->Body = sprintf(<<<'EOF'
Winning numbers: %s
Own numbers: %s
Intersection: %s
EOF
            , implode(', ', $winNumbers), implode(', ', $ownNumbers), implode(', ', $win));

        if (!$mail->send()) {
            $error = new \Exception($mail->ErrorInfo);
            $this->informAdmin($error);
            throw $error;
        }
    }

    /**
     * Informs the admin about an error.
     *
     * @param \Exception $e
     *
     * @throws \Exception
     */
    public function informAdmin(\Exception $e)
    {
        $mail = $this->scaffoldMail();
        $mail->addAddress(Environment::get('ADMIN'));

        $mail->Subject = 'ERROR - DON Adv Cal';

        $mail->Body = $e->getMessage();

        if (!$mail->send()) {
            throw new \Exception($mail->ErrorInfo, 0, $e);
        }
    }
}
