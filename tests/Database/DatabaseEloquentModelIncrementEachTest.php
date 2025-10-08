<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;

class DatabaseEloquentModelIncrementEachTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('points')->default(0);
            $table->integer('views')->default(0);
            $table->timestamps();
        });

        // Seed two test users
        UserIncrementEachStub::create(['points' => 5, 'views' => 10]);
        UserIncrementEachStub::create(['points' => 1, 'views' => 1]);
    }

    public function testIncrementEachAffectsOnlyCurrentModel()
    {
        $first = UserIncrementEachStub::first();
        $second = UserIncrementEachStub::skip(1)->first();

        $first->incrementEach(['points' => 2, 'views' => 3]);
        $first->refresh();
        $second->refresh();

        // ✅ The target model should be updated
        $this->assertSame(7, $first->points);
        $this->assertSame(13, $first->views);

        // ✅ Other models should be untouched
        $this->assertSame(1, $second->points);
        $this->assertSame(1, $second->views);

        // ✅ Sanity: only two records total
        $this->assertCount(2, UserIncrementEachStub::all());
    }

    public function testThrowsWhenUnsavedModel()
    {
        $this->expectException(LogicException::class);

        $user = new UserIncrementEachStub(['points' => 5]);
        $user->incrementEach(['points' => 1]);
    }

    public function testThrowsWhenNonNumericValuePassed()
    {
        $this->expectException(InvalidArgumentException::class);

        $user = UserIncrementEachStub::first();
        $user->incrementEach(['points' => 'abc']);
    }

    public function testDecrementEachAffectsOnlyCurrentModel()
    {
        $first = UserIncrementEachStub::first();
        $second = UserIncrementEachStub::skip(1)->first();

        $first->decrementEach(['points' => 2, 'views' => 3]);
        $first->refresh();
        $second->refresh();

        // ✅ The target model should be updated
        $this->assertSame(3, $first->points);
        $this->assertSame(7, $first->views);

        // ✅ The other should remain unchanged
        $this->assertSame(1, $second->points);
        $this->assertSame(1, $second->views);
    }
}

class UserIncrementEachStub extends Model
{
    protected $table = 'users';
    protected $guarded = [];
}
