<?php

namespace DeskPRO\ImporterTools\Importers\Isid;

use Application\DeskPRO\Entity\Brand;
use Application\DeskPRO\Entity\CustomDefTicket;
use Application\DeskPRO\Entity\Department;
use Application\DeskPRO\Entity\Language;
use DeskPRO\ImporterTools\AbstractImporter;
use Orb\Util\Arrays;

/**
 * Class CustomImporter.
 */
class IsidImporter extends AbstractImporter
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array
     */
    private $ticketCustomFields = [];

    /**
     * @var array
     */
    private $languages = [];

    /**
     * @var array
     */
    private $departments = [];

    /**
     * @var array
     */
    private $brands = [];

    /**
     * @var string
     */
    private $defaultLanguage = null;

    /**
     * @var string
     */
    private $defaultDepartment = null;

    /**
     * @var string
     */
    private $defaultBrand = null;


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

        $this->gatherCustomFields();
        $this->gatherLanguages();
        $this->gatherDepartments();
        $this->gatherBrands();

        $csv = $this->csv()->readFile($this->config['csv_path'], ',', '"');
        $tickets = [];
        $people = [];
        foreach($csv as $index => $datum) {
            echo "line ".$index.PHP_EOL;
            $questions = [];
            $answers = [];
            if($datum['answer']) {
                $answersPath = sprintf('%s%s%s', $this->config['tickets_path'],DIRECTORY_SEPARATOR, $datum['answer']);
                $answers = $this->collectMessages($answersPath, $datum['custom_data528'], 'answer');
            }
            $answersCount = count($answers);
            $answermemo = [];
            if($datum['answermemo']) {
                $answermemo = $this->collectMessages(
                    sprintf('%s%s%s', $this->config['tickets_path'],DIRECTORY_SEPARATOR, $datum['answermemo']), null,'answer'
                );
                foreach($answermemo as &$m) {
                    $m['index'] += $answersCount;
                    if($m['date']) {
                        $m['is_note'] = true;
                        $answers[] = $m;
                    }
                }
            }
            if($datum['question']) {
                $questionsPath = sprintf('%s%s%s', $this->config['tickets_path'], DIRECTORY_SEPARATOR, $datum['question']);
                $questions     = $this->collectMessages($questionsPath, $datum['custom_data528']);
            }
            $messages = $this->combineMessages($questions, $answers);
            if(!$messages) {
                $this->logEmptyMessages($datum);
                continue;
            }
            $subject = trim($datum['subject']) ?: explode("\n", $messages[0]['text'])[0];
            $people[$datum['agent']] = ['email' => $datum['agent'], 'is_agent' => 1];
            $people[$datum['user']] = ['email' => $datum['user'], 'is_agent' => 0];
            $ticket = [
                'status' => 'resolved',
                'agent' => $datum['agent'],
                'person' => $datum['user'],
                'ref' => $datum['ticket_id'],
                'urgency' => $datum['urgency'],
                'date_created' => $this->formatter()->getFormattedDate($datum['custom_data528']),
                'subject' => $subject,
                'labels' => [$datum['label']],
                'department' => $this->getDepartment($datum['department']),
                'brand' => $this->getBrand($datum['brand_id']),
                'language' => $this->getLanguage($datum['language']),
                'messages' => [
                ],
            ];

            foreach ($messages as $mIndex => $message) {
                $ticket['messages'][] = [
                    'oid' => "t-".$ticket['ref']."-m-".$mIndex,
                    'person' => $message['type'] === 'question' ? $datum['user'] : $datum['agent'],
                    'date_created' => $message['date'] ? $this->formatter()->getFormattedDate($message['date']->format('Y-m-d H:i:s')) : null,
                    'format' => 'text',
                    'message' => $message['text']
                ];
            }

            if(isset($datum['receiptmemo']) && $datum['receiptmemo']) {
                array_unshift($ticket['messages'], [
                    'oid' => "t-".$ticket['ref']."-receiptmemo",
                    'person' => $datum['agent'],
                    'format' => 'text',
                    'is_note' => true,
                    'date_created' => $ticket['date_created'],
                    'message' => file_get_contents($this->config['tickets_path'].DIRECTORY_SEPARATOR.$datum['receiptmemo']),
                ]);
            }
            foreach($answermemo as $m) {
                if(!$m['date']) {
                    $ticket['messages'][] = [
                        'oid' => "t-".$ticket['ref']."-answermemo",
                        'person' => $datum['agent'],
                        'date_created' =>  $ticket['date_created'],
                        'format' => 'text',
                        'message' => $m['text'],
                        'is_note' => true,
                    ];
               }
            }

            $customFields = [];
            foreach($datum as $i => $datumItem) {
                if(strpos($i, 'custom_data') !== false && $datumItem) {
                    $id = str_replace('custom_data', '', $i);
                    if(isset($this->ticketCustomFields[$id])) {
                        if(in_array($this->ticketCustomFields[$id]->getType(), ['datetime', 'date'])) {
                            $datumItem = $this->formatter()->getFormattedDate($datumItem);
                        }
                        $customFields[] = [
                            'name' => $this->ticketCustomFields[$id]->getTitle(),
                            'value' => $datumItem,
                        ];
                    }
                }
            }
            $ticket['custom_fields'] = $customFields;

            $dateResolved = null;
            $reversedMessages = array_reverse($ticket['messages']);
            foreach($reversedMessages as $message) {
                if($message['date_created']) {
                    $dateResolved = $message['date_created'];
                    break;
                }
            }
            if(!$dateResolved) {
                $dateResolved = $ticket['date_created'];
            }
            $ticket['date_resolved'] = $dateResolved;

            $tickets[] = $ticket;
        }

        foreach($tickets as $ticket) {
            $this->writer()->writeTicket($ticket['ref'], $ticket);
        }
    }

    /**
     *
     */
    private function gatherCustomFields()
    {
        $entityManager            = $this->container->get('doctrine.orm.default_entity_manager');
        $this->ticketCustomFields = $entityManager->getRepository(CustomDefTicket::class)->getEnabledTopFields();
    }

    /**
     *
     */
    private function gatherLanguages()
    {
        $entityManager         = $this->container->get('doctrine.orm.default_entity_manager');
        $entityRepository      = $entityManager->getRepository(Language::class);
        $this->languages       = $entityRepository->getTitles();
        $this->defaultLanguage = $entityRepository->getDefault()->getTitle();
    }

    /**
     *
     */
    private function gatherBrands()
    {
        $entityManager         = $this->container->get('doctrine.orm.default_entity_manager');
        $entityRepository      = $entityManager->getRepository(Brand::class);
        $this->brands          = $entityRepository->getNames();
        $defaultBrandId        = $this->container->get('settings_resolver')->getGlobalSettings()->get('portal.default_brand');
        $this->defaultBrand    = $this->brands[$defaultBrandId];
    }

    /**
     *
     */
    private function getBrand($id)
    {
        return isset($this->brands[$id]) && $this->brands[$id] ? $this->brands[$id] : $this->defaultBrand;
    }

    /**
     * @param $id
     *
     * @return mixed|string
     */
    private function getLanguage($id) {
        return isset($this->languages[$id]) && $this->languages[$id] ? $this->languages[$id] : $this->defaultLanguage;
    }

    /**
     *
     */
    private function gatherDepartments()
    {
        $entityManager     = $this->container->get('doctrine.orm.default_entity_manager');
        $entityRepository  = $entityManager->getRepository(Department::class);
        $deps              = $entityRepository->getTicketDepartments();
        foreach($deps as $dep) {
            $this->departments[$dep->getId()] = $dep->getTitle();
        }
        $this->defaultDepartment = $entityRepository->getDefaultDepartment('ticket')->getTitle();
    }

    private function getDepartment($id) {
        return isset($this->departments[$id]) && $this->departments[$id] ? $this->departments[$id] : $this->defaultDepartment;
    }

    /**
     * @param        $path
     * @param        $possibleDate
     * @param string $type
     *
     * @return array
     */
    private function collectMessages($path, $possibleDate, $type = 'question')
    {
        if(!file_exists($path) || is_dir($path)) {
            return [];
        }
        $messages = file_get_contents($path);
        $messages = preg_split('/-{70,}/', $messages);
        $messages = Arrays::removeEmptyString($messages);
        $newMessages = [];
        $timezone = $this->container->get('settings_resolver')->getGlobalSettings()->get('importer_timezone', 'UTC');

        if($possibleDate) {
            try {
                $possibleDate = new \DateTime($possibleDate, new \DateTimeZone($timezone));
            } catch(\Exception $e) {
                $possibleDate = null;
            }
        }
        $prev = null;
        foreach($messages as $index => $message) {
            $message = trim($message);
            $matches = [];
            $date = null;
            preg_match('#(\d{4}/\d{2}/\d{2})(\s\d{2}:\d{2}:\d{2})?#', $message, $matches);
            if(isset($matches[0])) {
                try {
                    $date = new \DateTime($matches[0], new \DateTimeZone($timezone));
                    if($date->format('H:i:s') === '00:00:00') {
                        $date->modify('+1 day')->modify('-1 sec');
                    }
                } catch (\Exception $e) {
                    $date = $possibleDate;
                }
            } else {
                $date = $possibleDate;
            }
            if($date) {
                $date->setTimezone(new \DateTimeZone('UTC'));
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

    /**
     * @param $questions
     * @param $answers
     *
     * @return array
     */
    protected function combineMessages($questions, $answers) {
        usort($questions, [$this, 'sortMessages']);
        usort($answers, [$this, 'sortMessages']);
        $answersCount = count($answers);
        $messages = [];
        $index = 0;
        foreach($questions as $index => $question) {
            $messages[] = $question;
            if(isset($answers[$index])) {
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

    /**
     * @param $m1
     * @param $m2
     *
     * @return int
     */
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

    /**
     * @param $datum
     */
    private function logEmptyMessages($datum) {
        echo sprintf('The ticket #%s has no messages!%s', $datum['ticket_id'], PHP_EOL);
    }
}
