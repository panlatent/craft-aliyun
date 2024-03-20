<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun;

use panlatent\craft\aliyun\services\Credentials;

/**
 * @property-read Credentials $credentials
 */
trait Services
{
    // Public Methods
    // =========================================================================

    public function getCredentials(): Credentials
    {
        return $this->get('credentials');
    }

    // Private Methods
    // =========================================================================

    private function _setServices(): void
    {
        $this->setComponents([
            'credentials' => Credentials::class
        ]);
    }
}