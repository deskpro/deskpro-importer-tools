<?php

namespace DeskPRO\ImporterTools\Importers\Isid;

use DeskPRO\ImporterTools\AbstractImporter;

/**
 * Class CustomImporter.
 */
class IsidImporter extends AbstractImporter
{
    private $config = [];

    /**
     * {@inheritdoc}
     */
    public function init(array $config)
    {
        if(!isset($config['tickets_path'])) {
            throw new \RuntimeException('Importer config does not have `tickets_path` key');
        }
        $config['csv_path'] = sprintf('%s%s%s', $config['tickets_path'], DIRECTORY_SEPARATOR, 'importTickets.csv');
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function testConfig()
    {
        if(!file_exists($this->config['csv_path'])) {
            throw new \RuntimeException(sprintf('We can\'t find importTickets.csv file in %s location', $this->config['tickets_path']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function runImport()
    {
        $csv = $this->csv()->readFile($this->config['csv_path'], ',', '"');
        foreach($csv as $datum) {
            $answersPath = sprintf('%s%s%s', $this->config['tickets_path'],DIRECTORY_SEPARATOR, $datum['answer']);
            $questionsPath = sprintf('%s%s%s', $this->config['tickets_path'],DIRECTORY_SEPARATOR, $datum['question']);
            $answers = $this->collectMessages($answersPath, $datum['custom_data528'], 'answer');
            $questions = $this->collectMessages($questionsPath, $datum['custom_data528']);
            $messages = $this->combineMessages($questions, $answers);
            $ticket = [
                'participants' => [$datum['agent'], $datum['user']],
                'agent' => $datum['user'],
                'user' => $datum['agent'],
                'ref' => $datum['ticket_id'],
            ];
        }
    }

    protected function collectMessages($path, $possibleDate, $type = 'question')
    {
        $messages = file_get_contents($path);
        $messages = preg_split('/-{70,}/', $messages);
        $newMessages = [];
        try {
            $possibleDate = new \DateTime($possibleDate);
        } catch(\Exception $e) {
            $possibleDate = null;
        }
        $prev = null;
        foreach($messages as $index => $message) {
            $message = trim($message);
            $matches = [];
            $date = null;
            preg_match('#(\d{4}/\d{2}/\d{2})(\s\d{2}:\d{2}:\d{2})?#', $message, $matches);
            if(isset($matches[0])) {
                try {
                    $date = new \DateTime($matches[0]);
                    if($date->format('H:i:s') === '00:00:00') {
                        $date->modify('+1 day')->modify('-1 sec');
                    }
                } catch (\Exception $e) {
                    $date = $possibleDate;
                }
            } else {
                $date = $possibleDate;
            }
            $newMessages[] = $prev = [
                'text' => $message,
                'date' => $date ? : ($prev && isset($prev['date']) ? $prev['date'] : null),
                'type' => $type,
                'index' => $index,
            ];
        }

        return $newMessages;
    }

    protected function combineMessages($questions, $answers) {
        usort($questions, [$this, 'sortMessages']);
        usort($answers, [$this, 'sortMessages']);
        $answersCount = count($answers);
        $messages = [];
        $index = 0;
        foreach($questions as $index => $question) {
            $messages[] = $question;
            if($answers[$index]) {
                $messages[] = $answers[$index];
            }
        }
        if($index < $answersCount - 1) {
            for(++$index; $index < $answersCount; ++$index) {
                $messages[] = $answers[$index];
            }
        }
        $self = $this;
        usort($messages, function($m1, $m2) use ($self) {
            if($m1['type'] === $m2['type']) {
                return $self->sortMessages($m1, $m2);
            } else {
                if($m1['type'] === 'question' && $m1['date']->getTimestamp() <= $m2['date']->getTimestamp()) {
                    return -1;
                } elseif($m2['type'] === 'question' && $m2['date']->getTimestamp() <= $m1['date']->getTimestamp()) {
                    return 1;
                }
            }

            return 0;
        });

        return $messages;
    }

    public function sortMessages($m1, $m2) {
        if($m1['date'] && $m2['date']) {
            return $m1['date']->getTimestamp() - $m2['date']->getTimestamp() ? : $m1['index'] - $m2['index'];
        } else {
            if($m1['date']) {
                return 1;
            } elseif($m2['date']) {
                return -1;
            } else {
                return $m1['index'] - $m2['index'];
            }
        }
    }
}
