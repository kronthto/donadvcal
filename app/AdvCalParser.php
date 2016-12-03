<?php

namespace App;

use Carbon\Carbon;
use GuzzleHttp\Client;
use simplehtmldom_1_5\simple_html_dom_node;
use Sunra\PhpSimple\HtmlDomParser;

class AdvCalParser
{
    const TODAY_WINNERS_URL = 'http://webmodule.schaetzl-druck.de/adv/today.html';

    /** @var Client */
    protected $client;

    /**
     * AdvCalParser constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Obtain a winning number from a table row.
     *
     * @param simple_html_dom_node $row
     *
     * @return int|null
     */
    protected function parseTableRow(simple_html_dom_node $row)
    {
        $fields = $row->find('td');
        if (count($fields) !== 3) {
            return null;
        }

        return (int) $fields[1]->innertext();
    }

    /**
     * Obtain all numbers that match a given dates winners.
     *
     * @param mixed|null $date
     *
     * @return array|\int[]
     *
     * @throws ParseException
     */
    public function getWins($date = null)
    {
        if (is_null($date)) {
            $date = Carbon::today();
        }
        if (!($date instanceof Carbon)) {
            $date = new Carbon($date);
        }

        $winningNumbers = [];
        if ($date->isToday()) {
            $result = $this->client->get(static::TODAY_WINNERS_URL);
            $dom = HtmlDomParser::str_get_html((string) $result->getBody());
            $tables = $dom->find('table[border=1]');
            if (count($tables) !== 1) {
                throw new ParseException('Could not find exactly one table');
            }
            foreach (reset($tables)->find('tr') as $tr) {
                $winningNumbers[] = $this->parseTableRow($tr);
            }
        }

        return array_filter($winningNumbers);
    }
}
