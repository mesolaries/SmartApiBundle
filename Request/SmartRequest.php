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


use Mesolaries\SmartApiBundle\Exception\SmartProblemException;
use Mesolaries\SmartApiBundle\Problem\SmartProblem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Emil Manafov <mnf.emil@gmail.com>
 * @author Cavid Huseynov <dev22843@gmail.com>
 * @author Shamsi Babakhanov <shamsi.b@list.ru>
 */
class SmartRequest
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ?Request
     */
    private $request;

    /**
     * @var ?SmartRequestRuleInterface
     */
    private $requestRule = null;

    private $isDebug;

    private $requestContent = [];

    private $requestContentInitial = [];

    private $validationErrors = [];

    /**
     * A bag to carry any necessary additional data
     *
     * @var array
     */
    private $bag = [];

    public function __construct(
        ValidatorInterface $validator,
        PropertyAccessorInterface $propertyAccessor,
        RequestStack $requestStack,
        bool $isDebug
    ) {
        $this->validator        = $validator;
        $this->propertyAccessor = $propertyAccessor;
        $this->request          = $requestStack->getCurrentRequest();
        $this->requestContent   = $this->requestContentInitial = $this->parseRequestContent($this->request);
        $this->isDebug          = $isDebug;
    }

    /**
     * Validates request body content against defined constraints in the $requestRule.
     * If all data is valid `process` method of the $requestRule and processors of every single field will be executed.
     *
     *
     * @param SmartRequestRuleInterface $requestRule Request rule to comply with
     * @param bool                      $skipMissing Skip missing fields in the Request body content instead of
     *                                               throwing an Exception
     *
     * @return array Request body content after all manipulations
     */
    public function comply(SmartRequestRuleInterface $requestRule, bool $skipMissing = false): array
    {
        $this->requestRule = $requestRule;

        $validationMap  = $this->requestRule->getValidationMap();
        $requestContent = $this->requestContent;

        if ($differ = array_diff_key($requestContent, $validationMap)) {
            if ($this->isDebug) {
                $smartProblem =
                    new SmartProblem(400, null, 'Undefined parameters were found in the request structure.');
                $smartProblem->addExtraData('errors', $differ);

                throw new SmartProblemException($smartProblem);
            }

            throw new BadRequestHttpException('Undefined parameters were found in the request structure.');
        }

        foreach ($validationMap as $key => $value) {
            if (!array_key_exists($key, $requestContent)) {
                if ($skipMissing) {
                    continue;
                } else {
                    if ($this->isDebug) {
                        $smartProblem =
                            new SmartProblem(400, null, 'Required parameter was not found in the request structure.');
                        $smartProblem->addExtraData('errors', $key);

                        throw new SmartProblemException($smartProblem);
                    }

                    throw new BadRequestHttpException('Required parameter was not found in the request structure.');
                }
            }

            $violations = [];

            if (isset($value['constraints'])) {
                $violations = $this->validator->validate($requestContent[$key], $value['constraints']);
            }

            if (0 !== count($violations)) {
                if (!$violations[0]->getPropertyPath()) {
                    $this->validationErrors[$key] = $violations[0]->getMessage();
                } else {
                    $this->validationErrors[$key] = [];

                    $this->propertyAccessor->setValue(
                        $this->validationErrors[$key],
                        $violations[0]->getPropertyPath(),
                        $violations[0]->getMessage()
                    );
                }
            }
        }

        if (0 !== count($this->validationErrors)) {
            $smartProblem = new SmartProblem(400, 'validation_error', 'There was a validation error.');
            $smartProblem->addExtraData('errors', $this->validationErrors);

            throw new SmartProblemException($smartProblem);
        }

        $this->runNormalizers();
        $this->runProcessors();

        return $this->requestContent;
    }

    /**
     * Validates the request parameters against a constraint or a list of constraints
     *
     * @param string                  $key         Request parameter key
     * @param Constraint|Constraint[] $constraints The constraint(s) to validate against
     * @param null|mixed              $default     Default value if the Request parameter not found
     *
     * @return mixed|null Validated value from the Request
     */
    public function validate(string $key, $constraints, $default = null)
    {
        $value = $this->request->get($key, $default);

        $violations = $this->validator->validate($value, $constraints);

        if (0 !== count($violations)) {
            $errors = [];

            foreach ($violations as $violation) {
                $errors[$key][] = $violation->getMessage();
            }

            $smartProblem = new SmartProblem(400, 'validation_error', 'There was a validation error.');
            $smartProblem->addExtraData('errors', $errors);

            throw new SmartProblemException($smartProblem);
        }

        return $value;
    }

    /**
     * Adds a new entry to the request body content or replaces an existing one
     *
     * @param string $key   A parameter key of the request body content to add or replace
     * @param mixed  $value A value for the selected key
     *
     * @return $this
     */
    public function manipulate(string $key, $value): self
    {
        $this->requestContent[$key] = $value;

        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getRequestRule(): ?SmartRequestRuleInterface
    {
        return $this->requestRule;
    }

    public function getRequestContent(): array
    {
        return $this->requestContent;
    }

    public function getRequestContentInitial(): array
    {
        return $this->requestContentInitial;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function addToBag(string $key, $value): self
    {
        $this->bag[$key] = $value;

        return $this;
    }

    public function getFromBag(string $key)
    {
        if (!array_key_exists($key, $this->bag)) {
            throw new \OutOfRangeException(sprintf('There is no value associated with the key "%s".', $key));
        }

        return $this->bag[$key];
    }

    public function getBag(): array
    {
        return $this->bag;
    }

    public function setBag(array $bag): SmartRequest
    {
        $this->bag = $bag;

        return $this;
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    /**
     * Runs `process` method of the SmartRequestRule and processors for every single field if exists
     *
     * @return void
     */
    private function runProcessors()
    {
        $this->requestRule->process($this);

        $validationMap = $this->requestRule->getValidationMap();

        foreach ($this->requestContent as $key => $value) {
            if (isset($validationMap[$key]['processor'])) {
                $processor = $validationMap[$key]['processor'];

                if (!is_callable($processor)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The "processor" option must be a valid callable ("%s" given).',
                            is_object($processor) ? get_class($processor) : gettype($processor)
                        )
                    );
                }

                call_user_func($processor, $this);
            }
        }
    }

    /**
     * Replaces the request parameter with the return value of the PHP callable
     *
     * @return void
     */
    private function runNormalizers()
    {
        $validationMap = $this->requestRule->getValidationMap();

        foreach ($this->requestContent as $key => $value) {
            if (isset($validationMap[$key]['normalizer'])) {
                $normalizer = $validationMap[$key]['normalizer'];

                if (!is_callable($normalizer)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The "normalizer" option must be a valid callable ("%s" given).',
                            is_object($normalizer) ? get_class($normalizer) : gettype($normalizer)
                        )
                    );
                }

                $this->requestContent[$key] = call_user_func($normalizer, $value);
            }
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function parseRequestContent(Request $request): array
    {
        if ('json' !== $request->getContentType()) {
            if ($request->getMethod() === 'GET') {
                return $request->query->all();
            }

            return $request->request->all();
        }

        $content = json_decode($request->getContent(), true);

        if (null === $content) {
            throw new SmartProblemException(
                new SmartProblem(400, 'invalid_body_format', 'Invalid JSON format sent.')
            );
        }

        return $content;
    }
}