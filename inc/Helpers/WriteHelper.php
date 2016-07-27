<?php

/*
 * DeskPRO (r) has been developed by DeskPRO Ltd. https://www.deskpro.com/
 * a British company located in London, England.
 *
 * All source code and content Copyright (c) 2016, DeskPRO Ltd.
 *
 * The license agreement under which this software is released
 * can be found at https://www.deskpro.com/eula/
 *
 * By using this software, you acknowledge having read the license
 * and agree to be bound thereby.
 *
 * Please note that DeskPRO is not free software. We release the full
 * source code for our software because we trust our users to pay us for
 * the huge investment in time and energy that has gone into both creating
 * this software and supporting our customers. By providing the source code
 * we preserve our customers' ability to modify, audit and learn from our
 * work. We have been developing DeskPRO since 2001, please help us make it
 * another decade.
 *
 * Like the work you see? Think you could make it better? We are always
 * looking for great developers to join us: http://www.deskpro.com/jobs/
 *
 * ~ Thanks, Everyone at Team DeskPRO
 */

namespace DeskPRO\ImporterTools\Helpers;

use Application\ImportBundle\Model;
use DeskPRO\Bundle\AppBundle\Form\Error\ValidatorErrorsGenerator;
use JMS\Serializer\Serializer;
use Orb\Util\Strings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ScriptHelper.
 */
class WriteHelper
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ValidatorErrorsGenerator
     */
    private $errorsGenerator;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var Model\PrimaryImportModelInterface
     */
    private $lastModel;

    /**
     * @return WriteHelper
     */
    public static function getHelper()
    {
        /** @var mixed $DP_CONTAINER */
        global $DP_CONTAINER;

        static $helper;
        if (null === $helper) {
            $helper = new self(
                $DP_CONTAINER->get('serializer'),
                $DP_CONTAINER->get('validator'),
                $DP_CONTAINER->get('form_error.validator_errors_generator.api'),
                $DP_CONTAINER->get('dp.importer_logger')
            );
        }

        return $helper;
    }

    /**
     * Constructor.
     *
     * @param Serializer               $serializer
     * @param ValidatorInterface       $validator
     * @param ValidatorErrorsGenerator $errorsGenerator
     * @param LoggerInterface          $logger
     */
    public function __construct(Serializer $serializer, ValidatorInterface $validator, ValidatorErrorsGenerator $errorsGenerator, LoggerInterface $logger)
    {
        $this->serializer      = $serializer;
        $this->validator       = $validator;
        $this->errorsGenerator = $errorsGenerator;
        $this->logger          = $logger;
    }

    /**
     * @param string $path
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function setOutputPath($path)
    {
        if (file_exists($path)) {
            if (!is_dir($path)) {
                throw new IOException("Output path '$path' is not a directory");
            }
            if (!is_readable($path) || !is_writable($path)) {
                throw new IOException("Unable to access $path");
            }
        } else {
            $fs = new Filesystem();
            $fs->mkdir($path);
        }

        $this->path = $path;
    }

    /**
     * @param int $page
     */
    public function setPage($page)
    {
        if ($page < 1) {
            throw new \RuntimeException('Page should be greater than 0.');
        }

        $this->page = (int) $page;
    }

    /**
     * Increment page num.
     */
    public function advancePage()
    {
        ++$this->page;
    }

    /**
     * @param string $oid
     *
     * @return string
     */
    public function userOid($oid)
    {
        return $oid ? 'user_'.$oid : null;
    }

    /**
     * @param string $oid
     *
     * @return string
     */
    public function agentOid($oid)
    {
        return $oid ? 'agent_'.$oid : null;
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeArticle($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Article::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeArticleCategory($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\ArticleCategory::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeArticleCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\ArticleCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeDownload($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Download::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeFeedback($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Feedback::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeFeedbackCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\FeedbackCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeNews($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\News::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeOrganization($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Organization::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeOrganizationCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\OrganizationCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     * @param bool       $oidWithPrefix
     */
    public function writeUser($oid, array $data, $oidWithPrefix = true)
    {
        if ($oidWithPrefix) {
            $oid = $this->userOid($oid);
        }

        $this->writePerson($oid, $data);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     * @param bool       $oidWithPrefix
     */
    public function writeAgent($oid, array $data, $oidWithPrefix = true)
    {
        if ($oidWithPrefix) {
            $oid = $this->agentOid($oid);
        }

        $this->writePerson($oid, array_merge($data, [
            'is_agent' => true,
        ]));
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writePersonCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\PersonCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTicket($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Ticket::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTicketCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\TicketCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTextSnippet($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\TextSnippet::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTextSnippetCategory($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\TextSnippetCategory::class);
    }

    public function printLastModel()
    {
        $this->logger->debug($this->serializer->serialize($this->lastModel, 'json'));
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    protected function writePerson($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Person::class);
    }

    /**
     * @param int    $oid
     * @param array  $rawData
     * @param string $modelClassName
     *
     * @throws \Exception
     */
    protected function writeModel($oid, array $rawData, $modelClassName)
    {
        $modelType = $this->getModelType($modelClassName);

        $this->lastModel = null;
        $this->logger->debug("Export $modelType #$oid");

        try {
            // transform to model
            $model = $this->serializer->fromArray($rawData, $modelClassName);
            $model->setOid($oid);
            $model->setRawData($rawData);

            $this->lastModel = $model;

            // validate model
            $errors = $this->validator->validate($model);
            if (count($errors)) {
                $encodedErrors = $this->serializer->serialize($this->errorsGenerator->generateValidatorErrors($errors), 'json');
                throw new \RuntimeException("$modelType #$oid validation is failed:\n$encodedErrors");
            }

            $encoded  = $this->serializer->serialize($model, 'json');
            $filePath = $this->getModelPath($model);

            if (!file_put_contents($filePath, $encoded)) {
                throw new \RuntimeException("Unable to write to '$filePath'");
            }
        } catch (\Exception $e) {
            $this->logger->error("Unable to write model: {$e->getMessage()}");
            $this->logger->error('Raw data:');
            $this->logger->error(json_encode($rawData));

            throw $e;
        }
    }

    /**
     * @param Model\PrimaryImportModelInterface $model
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getModelPath(Model\PrimaryImportModelInterface $model)
    {
        if (!$this->path) {
            throw new \Exception('Output path is not defined');
        }

        $entityPath     = $this->getModelType($model);
        $batchModelPath = sprintf('%s/%d/%s', $this->path, $this->page, $entityPath);

        $fs = new Filesystem();
        $fs->mkdir($batchModelPath);

        return "$batchModelPath/{$model->getOid()}.json";
    }

    /**
     * @param mixed $model
     *
     * @return string
     */
    protected function getModelType($model)
    {
        $modelType = (new \ReflectionClass($model))->getShortName();
        $modelType = Strings::camelCaseToUnderscore($modelType);

        return $modelType;
    }
}
