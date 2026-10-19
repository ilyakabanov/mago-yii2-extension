<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{Category, MyActiveRecord, User};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveRecord} static method return types in PHPStan analysis.
 *
 * Verifies type inference for {@see ActiveRecord::find()}, {@see ActiveRecord::findOne()},
 * {@see ActiveRecord::findAll()}, and {@see ActiveRecord::findBySql()} on custom {@see ActiveRecord} implementations,
 * covering chained calls, `class-string` receivers, and array versus object result scenarios.
 */
final class ActiveRecordStaticMethodReturnType
{
    public function testReturnCategoryArrayQueryWhenFindBySqlWithAsArray(): void
    {
        \Parity\assertType(Category::findBySql('SELECT * FROM categories')->asArray(), \parity_expected_2f78801b40f119f898f2());
    }

    public function testReturnCategoryQueryWhenFindOnCategory(): void
    {
        \Parity\assertType(Category::find(), \parity_expected_da024efc2552f4f4af84());
    }

    public function testReturnMyActiveRecordArrayQueryWhenFindAsArray(): void
    {
        \Parity\assertType(MyActiveRecord::find()->asArray(), \parity_expected_d888b09245ea7d2c995e());
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithIds(): void
    {
        \Parity\assertType(MyActiveRecord::findAll([1, 2, 3]), \parity_expected_e789012f4842b3650210());
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneAfterChaining(): void
    {
        \Parity\assertType(MyActiveRecord::find()->where(['status' => 'published'])->one(), \parity_expected_cd88352d7369660815bb());
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneById(): void
    {
        \Parity\assertType(MyActiveRecord::findOne(1), \parity_expected_cd88352d7369660815bb());
    }

    public function testReturnMyActiveRecordQueryWhenFind(): void
    {
        \Parity\assertType(MyActiveRecord::find(), \parity_expected_8f8c179ed541f0ecf701());
    }

    public function testReturnMyActiveRecordQueryWhenFindBySqlWithParameters(): void
    {
        \Parity\assertType(MyActiveRecord::findBySql('SELECT * FROM my_table WHERE id = :id', [':id' => 1]), \parity_expected_8f8c179ed541f0ecf701());
    }

    public function testReturnMyActiveRecordQueryWhenFindWithChaining(): void
    {
        \Parity\assertType(MyActiveRecord::find()->where(['status' => 'active'])->orderBy('created_at DESC'), \parity_expected_8f8c179ed541f0ecf701());
    }

    public function testReturnMyActiveRecordWhenInstantiating(): void
    {
        \Parity\assertType(new MyActiveRecord(), \parity_expected_a9c90bb1f4c06e6f7920());
    }

    public function testReturnUserArrayQueryWhenFindAsArray(): void
    {
        \Parity\assertType(User::find()->asArray(), \parity_expected_c5854e40548ef1ef4f5e());
    }

    public function testReturnUserArrayWhenFindAllAfterChaining(): void
    {
        \Parity\assertType(User::find()->where(['active' => 1])->orderBy('name ASC')->all(), \parity_expected_d3380165387272b360fc());
    }

    public function testReturnUserArrayWhenFindAllWithCondition(): void
    {
        \Parity\assertType(User::findAll(['status' => 'active']), \parity_expected_d3380165387272b360fc());
    }

    public function testReturnUserOrNullWhenFindOneByCondition(): void
    {
        \Parity\assertType(User::findOne(['id' => 1]), \parity_expected_49c1fd1330b7ca3211b1());
    }

    public function testReturnUserQueryWhenFindBySql(): void
    {
        \Parity\assertType(User::findBySql('SELECT * FROM users'), \parity_expected_6f36bca8673c409cd2a9());
    }

    public function testReturnUserQueryWhenFindBySqlWithChaining(): void
    {
        \Parity\assertType(User::findBySql('SELECT * FROM users')->andWhere(['active' => 1])->limit(10), \parity_expected_6f36bca8673c409cd2a9());
    }

    /**
     * @param class-string<User> $modelClass
     */
    public function testReturnUserQueryWhenFindOnClassStringVariable(string $modelClass): void
    {
        \Parity\assertType($modelClass::find(), \parity_expected_6f36bca8673c409cd2a9());
        \Parity\assertType($modelClass::findOne(1), \parity_expected_49c1fd1330b7ca3211b1());
        \Parity\assertType($modelClass::find()->asArray()->one(), \parity_expected_9e5e8581bd0d69c9f161());
    }

    public function testReturnUserQueryWhenFindOnUser(): void
    {
        \Parity\assertType(User::find(), \parity_expected_6f36bca8673c409cd2a9());
    }

    public function testReturnUserWhenInstantiating(): void
    {
        \Parity\assertType(new User(), \parity_expected_aa7c85838224e91c19a1());
    }
}
