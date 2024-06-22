<?php

namespace Tests\Unit;

use stdClass;
use Mockery as m;
use Tests\TestCase;
use Weirdo\Entrust\Entrust;

class EntrustTest extends TestCase
{
    protected $app;

    public function testHasRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $entrust = m::mock('Weirdo\Entrust\Entrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $entrust->shouldReceive('user')
            ->andReturn($user)
            ->twice()->ordered();

        $entrust->shouldReceive('user')
            ->andReturn(false)
            ->once()->ordered();

        $user->shouldReceive('hasRole')
            ->with('UserRole', false)
            ->andReturn(true)
            ->once();

        $user->shouldReceive('hasRole')
            ->with('NonUserRole', false)
            ->andReturn(false)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($entrust->hasRole('UserRole'));
        $this->assertFalse($entrust->hasRole('NonUserRole'));
        $this->assertFalse($entrust->hasRole('AnyRole'));
    }

    public function testCan()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $entrust = m::mock('Weirdo\Entrust\Entrust[user]', [$app]);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $entrust->shouldReceive('user')
            ->andReturn($user)
            ->twice()->ordered();

        $entrust->shouldReceive('user')
            ->andReturn(false)
            ->once()->ordered();

        $user->shouldReceive('can')
            ->with('user_can', false)
            ->andReturn(true)
            ->once();

        $user->shouldReceive('can')
            ->with('user_cannot', false)
            ->andReturn(false)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertTrue($entrust->can('user_can'));
        $this->assertFalse($entrust->can('user_cannot'));
        $this->assertFalse($entrust->can('any_permission'));
    }

    public function testUser()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->auth = m::mock('Auth');
        $entrust = new Entrust($app);
        $user = m::mock('_mockedUser');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->auth->shouldReceive('user')
            ->andReturn($user)
            ->once();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame($user, $entrust->user());
    }

    public function testRouteNeedsRole()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = new Entrust($app);

        $route = 'route';
        $oneRole = 'RoleA';
        $manyRole = ['RoleA', 'RoleB', 'RoleC'];

        $oneRoleFilterName  = $this->makeFilterName($route, [$oneRole]);
        $manyRoleFilterName = $this->makeFilterName($route, $manyRole);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($oneRoleFilterName, $manyRoleFilterName), m::type('Closure'))
            ->twice()->ordered();

        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($oneRoleFilterName, $manyRoleFilterName))
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertNull($entrust->routeNeedsRole($route, $oneRole));
        $this->assertNull($entrust->routeNeedsRole($route, $manyRole));
    }

    public function testRouteNeedsPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = new Entrust($app);

        $route = 'route';
        $onePerm = 'can_a';
        $manyPerm = ['can_a', 'can_b', 'can_c'];

        $onePermFilterName = $this->makeFilterName($route, [$onePerm]);
        $manyPermFilterName = $this->makeFilterName($route, $manyPerm);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(m::anyOf($onePermFilterName, $manyPermFilterName), m::type('Closure'))
            ->twice()->ordered();

        $app->router->shouldReceive('when')
            ->with($route, m::anyOf($onePermFilterName, $manyPermFilterName))
            ->twice();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertNull($entrust->routeNeedsPermission($route, $onePerm));
        $this->assertNull($entrust->routeNeedsPermission($route, $manyPerm));
    }

    public function testRouteNeedsRoleOrPermission()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $app = new stdClass();
        $app->router = m::mock('Route');
        $entrust = new Entrust($app);

        $route = 'route';
        $oneRole = 'RoleA';
        $manyRole = ['RoleA', 'RoleB', 'RoleC'];
        $onePerm = 'can_a';
        $manyPerm = ['can_a', 'can_b', 'can_c'];

        $oneRoleOnePermFilterName = $this->makeFilterName($route, [$oneRole], [$onePerm]);
        $oneRoleManyPermFilterName = $this->makeFilterName($route, [$oneRole], $manyPerm);
        $manyRoleOnePermFilterName = $this->makeFilterName($route, $manyRole, [$onePerm]);
        $manyRoleManyPermFilterName = $this->makeFilterName($route, $manyRole, $manyPerm);

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $app->router->shouldReceive('filter')
            ->with(
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                ),
                m::type('Closure')
            )
            ->times(4)->ordered();

        $app->router->shouldReceive('when')
            ->with(
                $route,
                m::anyOf(
                    $oneRoleOnePermFilterName,
                    $oneRoleManyPermFilterName,
                    $manyRoleOnePermFilterName,
                    $manyRoleManyPermFilterName
                )
            )
            ->times(4);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertNull($entrust->routeNeedsRoleOrPermission($route, $oneRole, $onePerm));
        $this->assertNull($entrust->routeNeedsRoleOrPermission($route, $oneRole, $manyPerm));
        $this->assertNull($entrust->routeNeedsRoleOrPermission($route, $manyRole, $onePerm));
        $this->assertNull($entrust->routeNeedsRoleOrPermission($route, $manyRole, $manyPerm));
    }

    public function simpleFilterDataProvider()
    {
        return [
            // Filter passes, null is returned
            [true, 'nullFilterTest'],
            // Filter fails, App::abort() is called
            [false, 'abortFilterTest', true],
            // Filter fails, custom response is returned
            [false, 'customResponseFilterTest', false, new stdClass()]
        ];
    }

    /**
     * @dataProvider simpleFilterDataProvider
     */
    public function testFilterGeneratedByRouteNeedsRole($returnValue, $filterTest, $abort = false, $expectedResponse = null)
    {
        $this->filterTestExecution('routeNeedsRole', 'hasRole', $returnValue, $filterTest, $abort, $expectedResponse);
    }

    /**
     * @dataProvider simpleFilterDataProvider
     */
    public function testFilterGeneratedByRouteNeedsPermission($returnValue, $filterTest, $abort = false, $expectedResponse = null)
    {
        $this->filterTestExecution('routeNeedsPermission', 'can', $returnValue, $filterTest, $abort, $expectedResponse);
    }

    protected function filterTestExecution($methodTested, $mockedMethod, $returnValue, $filterTest, $abort, $expectedResponse)
    {
        // Mock Objects
        $app = m::mock(\Illuminate\Foundation\Application::class);
        $app->shouldReceive('offsetGet')->once()->andReturnSelf();
        $entrust = m::mock("Weirdo\Entrust\Entrust[$mockedMethod]", [$app]);

        // Static values
        $route = 'route';
        $methodValue = 'role-or-permission';
        $filterName = $this->makeFilterName($route, [$methodValue]);

        $app->router->shouldReceive('when')->with($route, $filterName)->once();
        $app->router->shouldReceive('filter')->with($filterName, m::on($this->$filterTest))->once();

        if ($abort) {
            $app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();
        }

        $this->expectedResponse = $expectedResponse;

        $entrust->shouldReceive($mockedMethod)->with($methodValue, m::any(true, false))->andReturn($returnValue)->once();
        $entrust->$methodTested($route, $methodValue, $expectedResponse);
    }

    /**
     * @return array
     */
    public function routeNeedsRoleOrPermissionFilterDataProvider()
    {
        return [
            // Both role and permission pass, null is returned
            [true,  true,  'nullFilterTest'],
            [true,  true,  'nullFilterTest', true],
            // Role OR permission fail, require all is false, null is returned
            [false, true,  'nullFilterTest'],
            [true,  false, 'nullFilterTest'],
            // Role and/or permission fail, App::abort() is called
            [false, true,  'abortFilterTest', true,  true],
            [true,  false, 'abortFilterTest', true,  true],
            [false, false, 'abortFilterTest', false, true],
            [false, false, 'abortFilterTest', true,  true],
            // Role and/or permission fail, custom response is returned
            [false, true,  'customResponseFilterTest', true,  false, new stdClass()],
            [true,  false, 'customResponseFilterTest', true,  false, new stdClass()],
            [false, false, 'customResponseFilterTest', false, false, new stdClass()],
            [false, false, 'customResponseFilterTest', true,  false, new stdClass()]
        ];
    }

    /**
     * @dataProvider routeNeedsRoleOrPermissionFilterDataProvider
     */
    public function testFilterGeneratedByRouteNeedsRoleOrPermission(
        $roleIsValid,
        $permIsValid,
        $filterTest,
        $requireAll = false,
        $abort = false,
        $expectedResponse = null
    ) {
        $app = m::mock(\Illuminate\Foundation\Application::class);
        $app->shouldReceive('offsetGet')->once()->andReturnSelf();
        $entrust = m::mock('Weirdo\Entrust\Entrust[hasRole, can]', [$app]);

        // Static values
        $route = 'route';
        $roleName = 'UserRole';
        $permName = 'user-permission';
        $filterName = $this->makeFilterName($route, [$roleName], [$permName]);

        $app->router->shouldReceive('when')->with($route, $filterName)->once();
        $app->router->shouldReceive('filter')->with($filterName, m::on($this->$filterTest))->once();

        $entrust->shouldReceive('hasRole')->with($roleName, $requireAll)->andReturn($roleIsValid)->once();
        $entrust->shouldReceive('can')->with($permName, $requireAll)->andReturn($permIsValid)->once();

        if ($abort) {
            $app->shouldReceive('abort')->with(403)->andThrow('Exception', 'abort')->once();
        }

        $this->expectedResponse = $expectedResponse;

        $entrust->routeNeedsRoleOrPermission($route, $roleName, $permName, $expectedResponse, $requireAll);
    }
}
