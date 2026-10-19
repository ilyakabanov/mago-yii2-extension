<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{Category, MyActiveRecord, User};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveRecord} relation method return types in PHPStan analysis.
 *
 * Verifies type inference for {@see MyActiveRecord::hasMany()} and {@see MyActiveRecord::hasOne()} on custom
 * {@see ActiveRecord} implementations, covering chained calls, array versus object results, literal and `class-string`
 * class names, and conditional class arguments.
 */
final class ActiveRecordRelationReturnType
{
    public function testReturnCategoryArrayQueryWhenHasManyAsArray(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasMany(Category::class, ['parent_id' => 'id'])->asArray(), \parity_expected_2f78801b40f119f898f2());
    }

    public function testReturnCategoryArrayWhenHasManyAsArrayWithAll(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasMany(Category::class, ['parent_id' => 'id'])->asArray()->all(), \parity_expected_2a0b31d5943dcf3e79c1());
    }

    public function testReturnCategoryArrayWhenHasManyWithAll(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasMany(Category::class, ['parent_id' => 'id'])->all(), \parity_expected_6232b236ea4dd90fc6b5());
    }

    public function testReturnCategoryQueryWhenHasManyChainedWithOrderAndLimit(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasMany(Category::class, ['parent_id' => 'id'])->orderBy('name ASC')->limit(10), \parity_expected_da024efc2552f4f4af84());
    }

    public function testReturnCategoryQueryWhenHasManyWithCategoryClass(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasMany(Category::class, ['parent_id' => 'id']), \parity_expected_da024efc2552f4f4af84());
    }

    public function testReturnCategoryQueryWhenHasManyWithStringClass(): void
    {
        $model = new User();

        \Parity\assertType($model->hasMany('yii2\extensions\phpstan\tests\support\stub\Category', ['user_id' => 'id']), \parity_expected_da024efc2552f4f4af84());
    }

    public function testReturnUnionQueryWhenHasOneWithConditionalClass(bool $flag): void
    {
        $model = new MyActiveRecord();
        $query = $model->hasOne($flag ? User::class : Category::class, ['id' => 'user_id']);

        \Parity\assertType($query, \parity_expected_21993f573234435e7e05());
        \Parity\assertType($query->asArray(), \parity_expected_bf9b6c8b6c559e197a05());
    }

    public function testReturnUserArrayQueryWhenHasOneAsArray(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasOne(User::class, ['id' => 'user_id'])->asArray(), \parity_expected_c5854e40548ef1ef4f5e());
    }

    public function testReturnUserArrayWhenHasOneAsArrayWithOne(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasOne(User::class, ['id' => 'user_id'])->asArray()->one(), \parity_expected_9e5e8581bd0d69c9f161());
    }

    public function testReturnUserOrNullWhenHasOneWithOne(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasOne(User::class, ['id' => 'user_id'])->one(), \parity_expected_49c1fd1330b7ca3211b1());
    }

    public function testReturnUserQueryWhenHasOneChainedWithWhereConditions(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model
                ->hasOne(User::class, ['id' => 'user_id'])
                ->where(['active' => 1])
                ->andWhere(['status' => 'published']), \parity_expected_6f36bca8673c409cd2a9());
    }

    /**
     * @param class-string<User> $class
     */
    public function testReturnUserQueryWhenHasOneWithClassStringParameter(string $class): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasOne($class, ['id' => 'user_id']), \parity_expected_6f36bca8673c409cd2a9());
        \Parity\assertType($model->hasOne($class, ['id' => 'user_id'])->asArray()->one(), \parity_expected_9e5e8581bd0d69c9f161());
    }

    public function testReturnUserQueryWhenHasOneWithStringClass(): void
    {
        $model = new Category();

        \Parity\assertType($model->hasOne('yii2\extensions\phpstan\tests\support\stub\User', ['id' => 'user_id']), \parity_expected_6f36bca8673c409cd2a9());
    }

    public function testReturnUserQueryWhenHasOneWithUserClass(): void
    {
        $model = new MyActiveRecord();

        \Parity\assertType($model->hasOne(User::class, ['id' => 'user_id']), \parity_expected_6f36bca8673c409cd2a9());
    }
}
