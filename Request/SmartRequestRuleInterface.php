<?php

/*
 * This file is part of the mesolaries/SmartApiBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Emil Manafov <mnf.emil@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Mesolaries\SmartApiBundle\Request;

/**
 * @author Emil Manafov <mnf.emil@gmail.com>
 * @author Cavid Huseynov <dev22843@gmail.com>
 * @author Shamsi Babakhanov <shamsi.b@list.ru>
 */
interface SmartRequestRuleInterface
{
    /**
     * Returns an array of request body content field mapping.
     *
     * @return array
     */
    public function getValidationMap(): array;

    /**
     * Runs every time after validation succeeded.
     * Implementation of this method is optional.
     *
     * @param SmartRequest $smartRequest
     *
     * @return mixed
     */
    public function process(SmartRequest $smartRequest);
}