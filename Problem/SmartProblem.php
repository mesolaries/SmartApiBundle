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

namespace Mesolaries\SmartApiBundle\Problem;

use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 7807 Problem details representation
 *
 * @author Emil Manafov <mnf.emil@gmail.com>
 */
class SmartProblem
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string
     */
    private $title;

    private $extraData = [];

    public function __construct(int $statusCode, ?string $type = null, ?string $title = null)
    {
        $this->statusCode = $statusCode;

        if ($type === null) {
            $type = 'about:blank';
            $title =
                isset(Response::$statusTexts[$statusCode]) ? Response::$statusTexts[$statusCode]
                    : 'Unknown status code :(';
        }

        $this->type = $type;
        $this->title = $title ?? '';
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Adds extra data to the object as key => value pair
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addExtraData(string $key, $value): self
    {
        $this->extraData[$key] = $value;

        return $this;
    }

    /**
     * Transforms the object into an array
     *
     * @return array
     */
    public function normalize(): array
    {
        return array_merge(
            [
                'status' => $this->statusCode,
                'type' => $this->type,
                'title' => $this->title,
            ],
            $this->extraData
        );
    }
}