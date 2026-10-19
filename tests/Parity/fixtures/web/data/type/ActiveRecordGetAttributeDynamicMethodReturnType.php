<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{
    Article,
    ArticleDraft,
    Book,
    Measurement,
    ModelWithConflictingProperty,
    ModelWithMultipleBehaviors,
    NestedSetsModel,
    Post,
    User,
    VirtualAttributeRecord,
};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveRecord::getAttribute()} return types in PHPStan analysis.
 *
 * Verifies type inference from model PHPDoc and behavior property definitions, including tags inherited from parent
 * classes and traits, precedence of model properties over behavior properties on conflict, nullsafe receivers, and the
 * `mixed` fallback for unknown attributes and unpacked arguments.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnType
{
    public function testReturnInheritedPropertyTypeWhenGetAttributeOnModelWithoutOwnTags(): void
    {
        $article = new Article();

        \Parity\assertType($article->getAttribute('id'), \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($article->getAttribute('title'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($article->getAttribute('revision'), \parity_expected_a69c69291a42ebaa09d8());
        \Parity\assertType($article->getAttribute('password'), \parity_expected_3f8fee624f43b2a9d685());
        \Parity\assertType($article->getAttribute('isNewRecord'), \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnIntAndStringWhenGetAttributeWithMultipleBehaviors(): void
    {
        $model = new ModelWithMultipleBehaviors();

        \Parity\assertType($model->getAttribute('lft'), \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($model->getAttribute('slug'), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnIntWhenGetAttributeWithBehaviorPhpDoc(): void
    {
        $model = new NestedSetsModel();

        \Parity\assertType($model->getAttribute('lft'), \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($model->getAttribute('rgt'), \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($model->getAttribute('depth'), \parity_expected_6da88c34ba124c41f977());
    }

    public function testReturnMixedWhenGetAttributeArgumentIsUnpacked(): void
    {
        $post = new Post();

        \Parity\assertType($post->getAttribute(...['title']), \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnMixedWhenGetAttributeNamesVirtualGetters(): void
    {
        $record = new VirtualAttributeRecord();

        \Parity\assertType($record->getAttribute('label'), \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnMixedWhenGetAttributeOnRelationAndReadOnlyTags(): void
    {
        $book = new Book();

        \Parity\assertType($book->getAttribute('comments'), \parity_expected_3f8fee624f43b2a9d685());
        \Parity\assertType($book->getAttribute('author'), \parity_expected_3f8fee624f43b2a9d685());
        \Parity\assertType($book->getAttribute('label'), \parity_expected_3f8fee624f43b2a9d685());
        \Parity\assertType($book->getAttribute('secret'), \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnMixedWhenGetAttributeWithBehaviorPhpDoc(): void
    {
        $model = new NestedSetsModel();

        \Parity\assertType($model->getAttribute('unknown_attribute'), \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnMixedWhenGetAttributeWithModelPhpDoc(): void
    {
        $post = new Post();

        \Parity\assertType($post->getAttribute('unknown_attribute'), \parity_expected_3f8fee624f43b2a9d685());
    }

    public function testReturnNearestPropertyTypeWhenGetAttributeOnModelOverridingInheritedTags(): void
    {
        $draft = new ArticleDraft();

        \Parity\assertType($draft->getAttribute('title'), \parity_expected_25a45dc86aae4028edf8());
        \Parity\assertType($draft->getAttribute('note'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($draft->getAttribute('revision'), \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($draft->getAttribute('id'), \parity_expected_6da88c34ba124c41f977());
        \Parity\assertType($draft->getAttribute('slug'), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnNullablePropertyTypeWhenGetAttributeOnNullsafeReceiver(User|null $user): void
    {
        \Parity\assertType($user?->getAttribute('id'), \parity_expected_586cf00f65235bf62aa9());
    }

    public function testReturnResolvedPropertyTypeWhenGetAttributeOnModelWithGenericParent(): void
    {
        $measurement = new Measurement();

        \Parity\assertType($measurement->getAttribute('value'), \parity_expected_76a7e234dc54e9c605b2());
    }

    public function testReturnStringWhenGetAttributeWithModelPhpDoc(): void
    {
        $post = new Post();

        \Parity\assertType($post->getAttribute('title'), \parity_expected_473287f8298dba7163a8());
        \Parity\assertType($post->getAttribute('content'), \parity_expected_473287f8298dba7163a8());
    }

    public function testReturnStringWhenGetAttributeWithModelPhpDocTakesPrecedenceOverBehavior(): void
    {
        $model = new ModelWithConflictingProperty();

        \Parity\assertType($model->getAttribute('lft'), \parity_expected_473287f8298dba7163a8());
    }
}
