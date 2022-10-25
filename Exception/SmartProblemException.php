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

namespace Mesolaries\SmartApiBundle\Exception;

use Mesolaries\SmartApiBundle\Problem\SmartProblem;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author Emil Manafov <mnf.emil@gmail.com>
 */
class SmartProblemException extends HttpException
{
    /**
     * @var SmartProblem
     */
    private $smartProblem;

    public function __construct(
        SmartProblem $smartProblem,
        \Throwable $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        $this->smartProblem = $smartProblem;
        $statusCode = $smartProblem->getStatusCode();
        $message = $smartProblem->getTitle();

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    public function getSmartProblem(): SmartProblem
    {
        return $this->smartProblem;
    }
}
