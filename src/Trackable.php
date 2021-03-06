<?php

namespace Imtigger\LaravelJobStatus;

use Illuminate\Queue\SerializesModels;

trait Trackable
{
    use SerializesModels {
        __sleep as traitSleep;
    }

    /** @var int $statusId */
    protected $statusId;
    protected $progressNow = 0;
    protected $progressMax = 0;
    protected $shouldTrack = true;

    protected function setProgressMax($value)
    {
        $this->update(['progress_max' => $value]);
        $this->progressMax = $value;
    }

    protected function setProgressNow($value, $every = 1)
    {
        if ($value % $every === 0 || $value === $this->progressMax) {
            $this->update(['progress_now' => $value]);
        }
        $this->progressNow = $value;
    }

    protected function incrementProgress($offset = 1, $every = 1)
    {
        $value = $this->progressNow + $offset;
        $this->setProgressNow($value, $every);
    }

    protected function setInput(array $value)
    {
        $this->update(['input' => $value]);
    }

    protected function setOutput(array $value)
    {
        $this->update(['output' => $value]);
    }

    protected function update(array $data)
    {
        /** @var JobStatusUpdater $updater */
        $updater = app(JobStatusUpdater::class);
        $updater->update($this, $data);
    }

    protected function prepareStatus(array $data = [])
    {
        /** @var JobStatus $entityClass */
        $entityClass = app(config('job-status.model'));

        $data = array_merge(['type' => $this->getDisplayName()], $data);
        /** @var JobStatus $status */
        $status = $entityClass::query()->create($data);

        $this->statusId = $status->getKey();
    }

    protected function getDisplayName()
    {
        return method_exists($this, 'displayName') ? $this->displayName() : static::class;
    }

    public function getJobStatusId()
    {
        return $this->statusId;
    }

    public function __sleep()
    {
        if (!$this->statusId && $this->shouldTrack) {
            $this->prepareStatus();
        }

        return $this->traitSleep();
    }
}
