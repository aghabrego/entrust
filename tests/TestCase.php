<?php

namespace Tests;

use Closure;
use Exception;
use Symfony\Component\VarDumper\VarDumper;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var Closure
     */
    protected $nullFilterTest;
    
    /**
     * @var Closure
     */
    protected $abortFilterTest;

    /**
     * @var Closure
     */
    protected $customResponseFilterTest;

    protected $expectedResponse;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->nullFilterTest = function ($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $this->assertNull($filterClosure());

            return true;
        };

        $this->abortFilterTest = function ($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            try {
                $filterClosure();
            } catch (Exception $e) {
                $this->assertSame('abort', $e->getMessage());

                return true;
            }

            // If we've made it this far, no exception was thrown and something went wrong
            return false;
        };

        $this->customResponseFilterTest = function ($filterClosure) {
            if (!($filterClosure instanceof Closure)) {
                return false;
            }

            $result = $filterClosure();

            $this->assertSame($this->expectedResponse, $result);

            return true;
        };
    }

    /**
     * @param mixed $value
     * @return void
     */
    public function dd($value)
    {
        VarDumper::dump($value);

        exit(1);
    }

    /**
     * @param string $route
     * @param array $roles
     * @param array|null $permissions
     * @return string
     */
    protected function makeFilterName($route, array $roles, array $permissions = null)
    {
        if (is_null($permissions)) {
            return implode('_', $roles) . '_' . substr(md5($route), 0, 6);
        } else {
            return implode('_', $roles) . '_' . implode('_', $permissions) . '_' . substr(md5($route), 0, 6);
        }
    }
}
