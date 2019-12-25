<?php

declare(strict_types=1);

namespace OliverKlee\Seminars\SchedulerTasks;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This is the configuration for the e-mail notifier task.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class MailNotifierConfiguration implements AdditionalFieldProviderInterface
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param string[] $taskInfo Values of the fields from the add/edit task form
     * @param AbstractTask|null $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     *
     * @return string[][] a two-dimensional array
     *          array('Identifier' => array('fieldId' => array('code' => '', 'label' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $pageUid = $task instanceof MailNotifier ? (string)$task->getConfigurationPageUid() : '';
        $taskInfo['seminars_configurationPageUid'] = $pageUid;

        $fieldId = 'task-page-uid';
        $fieldCode = '<input type="text" name="tx_scheduler[seminars_configurationPageUid]" id="'
            . $fieldId . '" value="' . $pageUid . '" size="4" />';

        return [
            $fieldId => [
                'code' => $fieldCode,
                'label' => 'LLL:EXT:seminars/Resources/Private/Language/locallang.xlf:schedulerTasks.fields.page-uid',
            ],
        ];
    }

    /**
     * Validates the additional field values.
     *
     * @param string[] $submittedData an array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule reference to the scheduler backend module
     *
     * @return bool true if validation was OK (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        $submittedData['seminars_configurationPageUid'] = (int)$submittedData['seminars_configurationPageUid'];
        $pageUid = $submittedData['seminars_configurationPageUid'];
        $hasPageUid = $pageUid > 0 && \Tx_Oelib_Db::existsRecordWithUid('pages', $pageUid);
        if ($hasPageUid) {
            return true;
        }

        $schedulerModule->addMessage(
            $this->getLanguageService()->sL(
                'LLL:EXT:seminars/Resources/Private/Language/locallang.xlf:schedulerTasks.errors.page-uid'
            ),
            FlashMessage::ERROR
        );

        return false;
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'] ?? null;
    }

    /**
     * Takes care of saving the additional fields' values in the task.
     *
     * @param string[] $submittedData an array containing the data submitted by the add/edit task form
     * @param AbstractTask $task the task that is being configured
     *
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $pageUid = (int)($submittedData['seminars_configurationPageUid'] ?? 0);

        /** @var MailNotifier $task */
        $task->setConfigurationPageUid($pageUid);
    }
}
