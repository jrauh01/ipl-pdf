<?php

namespace ipl\Tests\Validator;

use InvalidArgumentException;
use ipl\Validator\CallbackValidator;
use ipl\Validator\ValidatorChain;
use LogicException;

class ValidatorChainTest extends TestCase
{
    public function testAddValidatorsWithNameToOptionsSpec()
    {
        $options = [
            'key' => 'value'
        ];

        $spec = [
            'test' => $options
        ];

        $validators = (new ValidatorChain())
            ->addValidatorLoader(__NAMESPACE__, 'Validator')
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(1, $validators);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[0]);
        $this->assertSame($options, $validatorsAsArray[0]->getOptions());
    }

    public function testAddValidatorsWithArraySpec()
    {
        $options = [
            'key' => 'value'
        ];

        $spec = [
            [
                'name'    => 'test',
                'options' => $options
            ]
        ];

        $validators = (new ValidatorChain())
            ->addValidatorLoader(__NAMESPACE__, 'Validator')
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(1, $validators);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[0]);
        $this->assertSame($options, $validatorsAsArray[0]->getOptions());
    }

    public function testAddValidatorsWithArraySpecButWithoutOptions()
    {
        $spec = [
            [
                'name' => 'test'
            ]
        ];

        $validators = (new ValidatorChain())
            ->addValidatorLoader(__NAMESPACE__, 'Validator')
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(1, $validators);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[0]);
        $this->assertNull($validatorsAsArray[0]->getOptions());
    }

    public function testAddValidatorsWithNameOnly()
    {
        $spec = [
            'test'
        ];

        $validators = (new ValidatorChain())
            ->addValidatorLoader(__NAMESPACE__, 'Validator')
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(1, $validators);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[0]);
        $this->assertNull($validatorsAsArray[0]->getOptions());
    }

    public function testAddValidatorsWithValidatorInstance()
    {
        $validator = new TestValidator();

        $spec = [
            $validator
        ];

        $validators = (new ValidatorChain())
            ->addValidatorLoader(__NAMESPACE__, 'Validator')
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(1, $validators);
        $this->assertSame($validator, $validatorsAsArray[0]);
    }

    public function testAddValidatorsWithAllSpecsMixed()
    {
        $options = [
            'key' => 'value'
        ];

        $validator = new TestValidator();

        $spec = [
            'test' => $options,
            [
                'name' => 'test'
            ],
            'test',
            $validator
        ];

        $validators = (new ValidatorChain())
            ->addValidatorLoader(__NAMESPACE__, 'Validator')
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(4, $validators);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[0]);
        $this->assertSame($options, $validatorsAsArray[0]->getOptions());
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[1]);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[2]);
        $this->assertSame($validator, $validatorsAsArray[3]);
    }

    public function testAddValidatorsRespectsDefaultValidatorLoader()
    {
        $spec = [
            'callback' => function () {
                return true;
            }
        ];

        $validators = (new ValidatorChain())
            ->addValidators($spec);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(1, $validators);
        $this->assertInstanceOf(CallbackValidator::class, $validatorsAsArray[0]);
    }

    public function testIsValid()
    {
        $validators = (new ValidatorChain())
            ->addValidators([
                new TestValidator()
            ]);

        $this->assertTrue($validators->isValid('value'));
        $this->assertSame([], $validators->getMessages());
    }

    public function testIsValidWithFailingValidator()
    {
        $validators = (new ValidatorChain())
            ->addValidators([
                new TestValidator(),
                new CallbackValidator(function ($value, CallbackValidator $validator) {
                    $validator->addMessage('Validation failed');

                    return false;
                }),
                new CallbackValidator(function ($value, CallbackValidator $validator) {
                    $validator->addMessage('Validation failed again');

                    return false;
                })
            ]);

        $this->assertFalse($validators->isValid('value'));
        $this->assertSame(['Validation failed', 'Validation failed again'], $validators->getMessages());
    }

    public function testIsValidWithValidatorThatBreaksTheChain()
    {
        $validators = (new ValidatorChain())
            ->addValidators([
                new TestValidator(),
                new CallbackValidator(function ($value, CallbackValidator $validator) {
                    $validator->addMessage('This validator should not get called');

                    return false;
                }),
            ]);

        $validators->add(
            new CallbackValidator(function ($value, CallbackValidator $validator) {
                $validator->addMessage('Validation failed');

                return false;
            }),
            true,
            ValidatorChain::DEFAULT_PRIORITY + 1
        );

        $this->assertFalse($validators->isValid('value'));
        $this->assertSame(['Validation failed'], $validators->getMessages());
    }

    public function testIsValidClearsMessages()
    {
        $validators = (new ValidatorChain())
            ->addValidators([
                new TestValidator(),
                new CallbackValidator(function ($value, CallbackValidator $validator) {
                    $validator->addMessage('Validation failed');

                    return false;
                }),
                new CallbackValidator(function ($value, CallbackValidator $validator) {
                    $validator->addMessage('Validation failed again');

                    return false;
                })
            ]);

        // Call isValid() more than once
        $this->assertFalse($validators->isValid('value'));
        $this->assertFalse($validators->isValid('value'));
        // Assert that we only have the messages from the last isValid() run
        $this->assertSame(['Validation failed', 'Validation failed again'], $validators->getMessages());
    }

    public function testMerge()
    {
        $validators = (new ValidatorChain())
            ->addValidators([
                new TestValidator()
            ]);

        $callbackValidator = new CallbackValidator(function ($value, CallbackValidator $validator) {
            $validator->addMessage('Validation failed');

            return false;
        });

        $validatorsToMerge = (new ValidatorChain())
            ->addValidators([
                new CallbackValidator(function ($value, CallbackValidator $validator) {
                    $validator->addMessage('This validator should not get called');

                    return false;
                })
            ])
            ->add(
                $callbackValidator,
                true,
                ValidatorChain::DEFAULT_PRIORITY + 1
            );

        $validators->merge($validatorsToMerge);

        $validatorsAsArray = $validators->toArray();

        $this->assertCount(3, $validators);
        $this->assertSame($callbackValidator, $validatorsAsArray[0]);
        $this->assertInstanceOf(TestValidator::class, $validatorsAsArray[1]);
        $this->assertInstanceOf(CallbackValidator::class, $validatorsAsArray[2]);
        $this->assertTrue($validators->getValidatorsThatBreakTheChain()->contains($callbackValidator));
    }

    public function testArraySpecExceptionIfNameIsMissing()
    {
        $spec = [
            [
            ]
        ];

        $this->expectException(InvalidArgumentException::class);

        (new ValidatorChain())->addValidators($spec);
    }

    public function testNameToOptionsSpecExceptionIfClassDoesNotExist()
    {
        $spec = [
            'doesnotexist' => null
        ];

        $this->expectException(InvalidArgumentException::class);

        (new ValidatorChain())->addValidators($spec);
    }

    public function testValidatorsWithoutSupportForEmptyValuesThrow()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This is expected');

        $chain = (new ValidatorChain())
            ->add(new CallbackValidator(function ($value) {
                if ($value === null) {
                    throw new LogicException('This is expected');
                }
            }));

        $chain->isValid(null);
    }
}
