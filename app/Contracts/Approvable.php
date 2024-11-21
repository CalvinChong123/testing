<?php

namespace App\Contracts;

interface Approvable
{
    /**
     * Apply the approval logic for the model.
     * 
     * @return void
     */
    public function applyApproval(array $data): void;

    /**
     * Revert or reject the approval request.
     * 
     * @return void
     */
    public function rejectApproval(): void;

    /**
     * Get the data to create an approval request.
     * 
     * @return array
     */
    public function getApprovalData(): array;
}
