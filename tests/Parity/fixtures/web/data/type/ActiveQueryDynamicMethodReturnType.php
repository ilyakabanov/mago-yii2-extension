<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\{ActiveQuery, ActiveRecord, Exception};
use yii2\extensions\phpstan\tests\support\stub\{
    Article,
    ArticleDraft,
    Book,
    Category,
    Comment,
    Ledger,
    Measurement,
    MyActiveRecord,
    Post,
    User,
    Volume,
};

use function array_key_exists;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveQuery} dynamic method return types in PHPStan analysis.
 *
 * Verifies type inference for query and result methods on custom {@see ActiveRecord} implementations, covering chained
 * calls, generic and non-generic custom query classes, array versus object result scenarios, row shapes built from
 * inherited `@property` tags, optional keys for relation and read-only tags, nullsafe receivers, and unpacked
 * arguments.
 */
final class ActiveQueryDynamicMethodReturnType
{
    public function testReturnActiveQueryWhenAsArrayOnNonGenericCustomQuery(): void
    {
        $arrayQuery = Comment::find()->asArray();

        \Parity\assertType($arrayQuery, \parity_expected_584574c6e3ba8b5f5810());
        \Parity\assertType($arrayQuery->one(), \parity_expected_606b86cf5e84d2a69a0e());
        \Parity\assertType(Comment::find()->asArray(false), \parity_expected_ba7e240c8b4191a020c1());
    }

    public function testReturnActiveQueryWhenAsArrayWithVariableArgument(): void
    {
        $userPreference = $_POST['format'] ?? 'default';
        $useArrayFormat = ($userPreference === 'json');

        \Parity\assertType(MyActiveRecord::find()->asArray($useArrayFormat), \parity_expected_0c5b21f60fbad13f9d15());
    }

    public function testReturnActiveQueryWhenCustomQuerySubclass(): void
    {
        $customQuery = Post::find();

        \Parity\assertType($customQuery, \parity_expected_9ad2aed4a9691f1670c7());
        \Parity\assertType($customQuery->asArray(), \parity_expected_75fe698399e36594661a());
        \Parity\assertType($customQuery->one(), \parity_expected_63b2edeb903808e21826());
        \Parity\assertType($customQuery->all(), \parity_expected_00250ebb588b44018856());
        \Parity\assertType($customQuery->published(), \parity_expected_9ad2aed4a9691f1670c7());
    }

    public function testReturnArrayShapeQueryWhenAsArrayOnArrayRows(): void
    {
        $asArray = getenv('RESPONSE_FORMAT') === 'array';

        $arrayQuery = MyActiveRecord::find()
            ->asArray()
            ->limit(10);

        \Parity\assertType($arrayQuery->asArray(), \parity_expected_d888b09245ea7d2c995e());
        \Parity\assertType($arrayQuery->asArray($asArray), \parity_expected_d888b09245ea7d2c995e());
    }

    public function testReturnArrayShapeWithInheritedRelationAndReadOnlyKeysWhenAsArray(): void
    {
        $arrayQuery = Volume::find()->asArray();

        \Parity\assertType($arrayQuery, \parity_expected_ffa6d10907a6e7f2ac3f());
        \Parity\assertType($arrayQuery->one(), \parity_expected_21c9138172bd96487f3d());
        \Parity\assertType($arrayQuery->all(), \parity_expected_98848f622a73d3cfcfdc());

        $row = $arrayQuery->one();

        if ($row !== null) {
            \Parity\assertType($row['id'], \parity_expected_6da88c34ba124c41f977());
            \Parity\assertType($row['comments'] ?? [], \parity_expected_e52c8a84c620d047cc42());
            \Parity\assertType($row['reviewer'] ?? null, \parity_expected_d6f14a74296e7d5de18b());
            \Parity\assertType($row['label'] ?? null, \parity_expected_25a45dc86aae4028edf8());
        }
    }

    public function testReturnClassifiedKeysWhenAsArrayOnModelWithBorderlineTagTypes(): void
    {
        $arrayQuery = Ledger::find()->asArray();

        \Parity\assertType($arrayQuery, \parity_expected_a86087305a3d31df66f6());

        $row = $arrayQuery->one();

        if ($row !== null) {
            \Parity\assertType($row['tags'], \parity_expected_164236dd58ccef737b17());
            \Parity\assertType($row['attachment'], \parity_expected_b06295fe8971888223c4());
            \Parity\assertType($row['payload'], \parity_expected_3f8fee624f43b2a9d685());
            \Parity\assertType($row['options'], \parity_expected_f8836dfea71a673cc20c());
            \Parity\assertType($row['status'], \parity_expected_cbfe90ed9b868864ec0f());
            \Parity\assertType($row['owner'], \parity_expected_9559ced2ae55730400dc());
            \Parity\assertType($row['drafts'] ?? [], \parity_expected_e52c8a84c620d047cc42());
            \Parity\assertType($row['thread'] ?? [], \parity_expected_e52c8a84c620d047cc42());
        }
    }

    public function testReturnCommentWhenNonGenericCustomQueryChained(): void
    {
        $customQuery = Comment::find()->approved();

        \Parity\assertType($customQuery, \parity_expected_ba7e240c8b4191a020c1());
        \Parity\assertType($customQuery->one(), \parity_expected_f1a802979b49b00e76d7());
        \Parity\assertType($customQuery->all(), \parity_expected_dd680c7b020808eae397());
    }

    public function testReturnDeclaredQueryWhenAsArrayArgumentIsUnpacked(): void
    {
        \Parity\assertType(MyActiveRecord::find()->asArray(...[false]), \parity_expected_8f8c179ed541f0ecf701());
        \Parity\assertType(MyActiveRecord::find()->asArray(...[true]), \parity_expected_bf9b6c8b6c559e197a05());
    }

    public function testReturnInheritedArrayShapeWhenAsArrayOnModelWithoutOwnTags(): void
    {
        $arrayQuery = Article::find()->asArray();

        \Parity\assertType($arrayQuery, \parity_expected_59216a9581f79eb14223());

        $row = $arrayQuery->one();

        \Parity\assertType($row, \parity_expected_deb831959e233809c71a());

        if ($row !== null) {
            \Parity\assertType($row['id'], \parity_expected_6da88c34ba124c41f977());
        }
    }

    public function testReturnMergedArrayShapeWhenAsArrayOnModelOverridingInheritedTags(): void
    {
        \Parity\assertType(ArticleDraft::find()->asArray(), \parity_expected_b4ded3c502ba7c9892a9());
    }

    public function testReturnModelOrArrayShapeQueryWhenAsArrayOnModelWithRelationTagsAndNonConstantArgument(): void
    {
        $asArray = getenv('RESPONSE_FORMAT') === 'array';

        \Parity\assertType(Book::find()->asArray(false), \parity_expected_d11aa3aaa3ce1e7ffafb());
        \Parity\assertType(Book::find()->asArray($asArray), \parity_expected_e13ef962aebf0bffe94a());
    }

    public function testReturnMyActiveRecordArrayQueryWhenAsArrayExplicitTrue(): void
    {
        \Parity\assertType(MyActiveRecord::find()->asArray(true), \parity_expected_d888b09245ea7d2c995e());
    }

    public function testReturnMyActiveRecordArrayQueryWhenChainedWithAsArray(): void
    {
        $complexQuery = MyActiveRecord::find()
            ->where(['status' => 'active'])
            ->asArray()
            ->orderBy('created_at DESC')
            ->limit(10);

        \Parity\assertType($complexQuery, \parity_expected_d888b09245ea7d2c995e());
        \Parity\assertType($complexQuery->all(), \parity_expected_6a280f5b2f3cfbb07d02());
    }

    public function testReturnMyActiveRecordArrayWhenArraysWithCondition(): void
    {
        $arrayRecords = MyActiveRecord::find()->asArray()->where(['flag' => true])->all();

        \Parity\assertType($arrayRecords, \parity_expected_6a280f5b2f3cfbb07d02());

        foreach ($arrayRecords as $record) {
            \Parity\assertType($record, \parity_expected_5455e29b6cfcd8b4cb5e());
            \Parity\assertType($record['flag'], \parity_expected_b760f44fa5965c2474a3());
        }
    }

    public function testReturnMyActiveRecordArrayWhenAsArrayWithAll(): void
    {
        $arrayQuery = MyActiveRecord::find()->asArray();

        \Parity\assertType($arrayQuery, \parity_expected_d888b09245ea7d2c995e());
        \Parity\assertType($arrayQuery->all(), \parity_expected_6a280f5b2f3cfbb07d02());
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithCondition(): void
    {
        $modelRecords = MyActiveRecord::findAll('condition');

        \Parity\assertType($modelRecords, \parity_expected_e789012f4842b3650210());

        foreach ($modelRecords as $record) {
            \Parity\assertType($record, \parity_expected_a9c90bb1f4c06e6f7920());
            \Parity\assertType($record->flag, \parity_expected_b760f44fa5965c2474a3());
        }
    }

    public function testReturnMyActiveRecordArrayWhenObjectsWithCondition(): void
    {
        $objectRecords = MyActiveRecord::find()
            ->asArray(false)
            ->where(['condition'])->all();

        \Parity\assertType($objectRecords, \parity_expected_e789012f4842b3650210());

        foreach ($objectRecords as $record) {
            \Parity\assertType($record, \parity_expected_a9c90bb1f4c06e6f7920());
            \Parity\assertType($record->flag, \parity_expected_b760f44fa5965c2474a3());
            \Parity\assertType($record['flag'], \parity_expected_3f8fee624f43b2a9d685());
        }
    }

    /**
     * @throws Exception if an unexpected error occurs during execution.
     */
    public function testReturnMyActiveRecordOrNullWhenChainedWithOne(): void
    {
        $offsetProp = 'flag';
        $flag = false;

        \Parity\assertType($offsetProp, \parity_expected_d5a49eb0b35f6e753511());
        \Parity\assertType($flag, \parity_expected_fcbcf165908dd18a9e49());

        $records = MyActiveRecord::find()
            ->where(['flag' => true])
            ->one();

        \Parity\assertType($records, \parity_expected_cd88352d7369660815bb());

        if ($records !== null) {
            \Parity\assertType($records, \parity_expected_a9c90bb1f4c06e6f7920());
            \Parity\assertType($records[$offsetProp], \parity_expected_3f8fee624f43b2a9d685());
            \Parity\assertType($records->flag, \parity_expected_b760f44fa5965c2474a3());
            \Parity\assertType($records->save(), \parity_expected_b760f44fa5965c2474a3());
        }
    }

    public function testReturnMyActiveRecordOrNullWhenFindBySqlWithOne(): void
    {
        $queryFromSql = MyActiveRecord::findBySql('SELECT * FROM table');

        \Parity\assertType($queryFromSql, \parity_expected_8f8c179ed541f0ecf701());

        $recordOne = $queryFromSql->one();

        \Parity\assertType($recordOne, \parity_expected_cd88352d7369660815bb());

        if ($recordOne !== null) {
            \Parity\assertType($recordOne->flag, \parity_expected_b760f44fa5965c2474a3());
            \Parity\assertType($recordOne['flag'], \parity_expected_3f8fee624f43b2a9d685());
        }
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneWithCondition(): void
    {
        $records = MyActiveRecord::findOne(['condition']);

        \Parity\assertType($records, \parity_expected_cd88352d7369660815bb());

        if ($records !== null) {
            \Parity\assertType($records->flag, \parity_expected_b760f44fa5965c2474a3());
            \Parity\assertType($records['flag'], \parity_expected_3f8fee624f43b2a9d685());
        }
    }

    public function testReturnMyActiveRecordQueryWhenAsArrayExplicitFalse(): void
    {
        \Parity\assertType(MyActiveRecord::find()->asArray(false), \parity_expected_8f8c179ed541f0ecf701());
    }

    public function testReturnMyActiveRecordQueryWhenAsArrayWithNamedArgument(): void
    {
        \Parity\assertType(MyActiveRecord::find()->asArray(value: false), \parity_expected_8f8c179ed541f0ecf701());
        \Parity\assertType(MyActiveRecord::find()->asArray(value: true), \parity_expected_d888b09245ea7d2c995e());
    }

    public function testReturnMyActiveRecordQueryWhenChainedWithConditions(): void
    {
        $query = MyActiveRecord::find();

        \Parity\assertType($query, \parity_expected_8f8c179ed541f0ecf701());
        \Parity\assertType($query->where(['active' => 1]) -> andWhere(['status' => 'published']), \parity_expected_8f8c179ed541f0ecf701());
    }

    public function testReturnNullableArrayShapeQueryWhenAsArrayOnNullsafeReceiver(User|null $user): void
    {
        \Parity\assertType($user?->hasOne(Category::class, ['id' => 'category_id'])->asArray(), \parity_expected_3f71691e58a155675a93());
        \Parity\assertType($user?->hasOne(Category::class, ['id' => 'category_id'])->asArray(false), \parity_expected_1d9443983ca678dc8c5a());
    }

    public function testReturnOptionalRelationAndReadOnlyKeysWhenAsArrayOnModelWithRelationTags(): void
    {
        $arrayQuery = Book::find()->asArray();

        \Parity\assertType($arrayQuery, \parity_expected_bafb8787d69ccdd67b7c());
        \Parity\assertType($arrayQuery->one(), \parity_expected_0c8e74fbe3fb6dc0073d());
        \Parity\assertType($arrayQuery->all(), \parity_expected_6a7f237bbab93b61831f());
    }

    public function testReturnPostArrayWhenCustomQuerySubclassAsArray(): void
    {
        $arrayQuery = Post::find()->asArray();

        \Parity\assertType($arrayQuery->one(), \parity_expected_d363195c51c199ffabf2());
        \Parity\assertType($arrayQuery->published(), \parity_expected_75fe698399e36594661a());
    }

    public function testReturnRelationRowsWhenReadingKeysOfAsArrayRow(): void
    {
        $row = Book::find()->asArray()->one();

        if ($row !== null) {
            \Parity\assertType($row['id'], \parity_expected_6da88c34ba124c41f977());
            \Parity\assertType($row['title'], \parity_expected_25a45dc86aae4028edf8());
            \Parity\assertType($row['comments'] ?? [], \parity_expected_e52c8a84c620d047cc42());
            \Parity\assertType($row['author'] ?? null, \parity_expected_d6f14a74296e7d5de18b());
            \Parity\assertType($row['label'] ?? null, \parity_expected_25a45dc86aae4028edf8());

            if (isset($row['comments'])) {
                \Parity\assertType($row['comments'], \parity_expected_e52c8a84c620d047cc42());
            }

            if (array_key_exists('author', $row)) {
                \Parity\assertType($row['author'], \parity_expected_d6f14a74296e7d5de18b());
            }

            if (isset($row['label'])) {
                \Parity\assertType($row['label'], \parity_expected_473287f8298dba7163a8());
            }
        }

        foreach (Book::find()->asArray()->all() as $record) {
            foreach ($record['comments'] ?? [] as $comment) {
                \Parity\assertType($comment, \parity_expected_5aa01f8073fe02702397());
            }
        }
    }

    public function testReturnResolvedArrayShapeWhenAsArrayOnModelWithGenericParent(): void
    {
        \Parity\assertType(Measurement::find()->asArray(), \parity_expected_74e7f830531736a00fa5());
    }

    public function testReturnUnionResultsWhenAsArrayWithVariableArgument(): void
    {
        $configValue = getenv('RESPONSE_FORMAT');

        $asArray = $configValue === 'array';

        $results = MyActiveRecord::find()->asArray($asArray)->all();

        \Parity\assertType($results, \parity_expected_a35683f217d511ee155c());

        foreach ($results as $result) {
            \Parity\assertType($result, \parity_expected_d1af16855cb406a319ea());
        }
    }
}
