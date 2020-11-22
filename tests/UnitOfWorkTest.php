<?php

namespace Sgiberne\UnitOfWork\Tests;

use PHPUnit\Framework\TestCase;
use Sgiberne\DatabaseTools\DatabaseOperator\MysqlOperator;
use Sgiberne\UnitOfWork\Adapter\PDOAdapter;
use Sgiberne\UnitOfWork\Collection\EntityCollection;
use Sgiberne\UnitOfWork\Storage\ObjectStorage;
use Sgiberne\UnitOfWork\Tests\Data\DataMapper\ActorDataMapper;
use Sgiberne\UnitOfWork\Tests\Data\Entity\Actor;
use Sgiberne\UnitOfWork\UnitOfWork;

final class UnitOfWorkTest extends TestCase
{

    private ?UnitOfWork $unitOfWork;


    public const DATABASE_USER = 'root';
    public const DATABASE_PASSWORD = 'password';
    public const DATABASE_HOST = 'mysql';
    public const DATABASE_PORT = '3306';
    private const DATABASE_NAME = 'unit_of_work_test';

    public static function setUpBeforeClass(): void
    {
        $mysqlOperator = new MysqlOperator();
        $mysqlOperator->connect(self::getDsn(),self::DATABASE_USER, self::DATABASE_PASSWORD );
        $mysqlOperator->createDatabase(self::DATABASE_NAME);
        $mysqlOperator->useDatabase(self::DATABASE_NAME);

        $sqlFile = __DIR__.'/Data/Sql/create_table_actor.sql';
        if (!is_file($sqlFile) || !is_readable($sqlFile)) {
            throw new \RuntimeException("$sqlFile doesn't not exist or is not readable");
        }

        $mysqlOperator->executeSql(file_get_contents($sqlFile));
        $mysqlOperator->disconnect();
    }

    public static function tearDownAfterClass(): void
    {
        $mysqlOperator = new MysqlOperator();
        $mysqlOperator->connect(self::getDsn(),self::DATABASE_USER, self::DATABASE_PASSWORD );
        $mysqlOperator->dropDatabase(self::DATABASE_NAME);
        $mysqlOperator->disconnect();
    }

    private static function getDsn(): string
    {
        return sprintf("mysql:dbname=;host=%s;port=%s", self::DATABASE_HOST, self::DATABASE_PORT);
    }

    public function setUp(): void
    {
        $adapter = new PDOAdapter(
            sprintf('mysql:dbname=%s;host=%s;port=%s', self::DATABASE_NAME, self::DATABASE_HOST, self::DATABASE_PORT),
            self::DATABASE_USER,
            self::DATABASE_PASSWORD
        );
        $this->unitOfWork = new UnitOfWork(
            new ActorDataMapper($adapter, new EntityCollection()),
            new ObjectStorage()
        );
    }

    public function tearDown(): void
    {
        $this->unitOfWork = null;
    }

    public function testEmptyData(): void
    {
        $this->assertNull($this->unitOfWork->fetchAll());
    }

    /**
     * @depends testEmptyData
     */
    public function testRegisterNewAndFetchById(): void
    {
        $actor = new Actor(null, 'Alfred', 'TI SONSON', 'google.fr/myAvatar.jpeg');
        $actor2 = new Actor(null, 'Amandine', 'KAKI', 'laposte.fr/amandine.jpeg');
        $actor3 = new Actor(null, 'Sarah', 'ALBERT', 'sgiberne.fr/sarah.jpeg');

        $this->unitOfWork->registerNew($actor);
        $this->unitOfWork->registerNew($actor2);
        $this->unitOfWork->registerNew($actor3);
        $this->unitOfWork->commit();

        /** @var Actor $actorFromDatabase */
        $actorFromDatabase = $this->unitOfWork->fetchById(1);

        $this->assertInstanceOf(Actor::class, $actorFromDatabase);
        $this->assertSame(1, $actorFromDatabase->id);
        $this->assertSame($actor->firstname, $actorFromDatabase->firstname);
        $this->assertSame($actor->lastname, $actorFromDatabase->lastname);
        $this->assertSame($actor->urlAvatar, $actorFromDatabase->urlAvatar);
    }

    /**
     * @depends testRegisterNewAndFetchById
     */
    public function testFetchAll(): void
    {
        $actorsFromDatabase = $this->unitOfWork->fetchAll([], [], [], ['id' => 'ASC']);

        $this->assertInstanceOf(EntityCollection::class, $actorsFromDatabase);
        $this->assertCount(3, $actorsFromDatabase);

        foreach ($actorsFromDatabase->toArray() as $key => $actor) {
            $this->assertInstanceOf(Actor::class, $actor);
            $this->assertSame($key + 1, $actor->id);
        }
    }

    /**
     * @depends testRegisterNewAndFetchById
     */
    public function testFetchAllWithWhere(): void
    {
        $actorsFromDatabase = $this->unitOfWork->fetchAll([], ['firstname = "Sarah"']);

        $this->assertInstanceOf(EntityCollection::class, $actorsFromDatabase);
        $this->assertCount(1, $actorsFromDatabase);

        foreach ($actorsFromDatabase->toArray() as $actor) {
            $this->assertInstanceOf(Actor::class, $actor);
            $this->assertSame(3, $actor->id);
            $this->assertSame('Sarah', $actor->firstname);
        }

        $actorsFromDatabase = $this->unitOfWork->fetchAll([], ['firstname = "Sarah"', 'OR' => "url_avatar = 'laposte.fr/amandine.jpeg'"]);

        $this->assertInstanceOf(EntityCollection::class, $actorsFromDatabase);
        $this->assertCount(2, $actorsFromDatabase);
    }

    /**
     * @depends testRegisterNewAndFetchById
     */
    public function testRegisterDirty(): void
    {
        $actor = $this->unitOfWork->fetchById(3);

        $actor->lastname = 'KARIMA';
        $this->unitOfWork->registerDirty($actor);
        $this->unitOfWork->commit();

        $newActor = $this->unitOfWork->fetchById(3);

        $this->assertSame($actor->lastname, $newActor->lastname);
    }

    /**
     * @depends testRegisterDirty
     */
    public function testRegisterDeleted(): void
    {
        $this->unitOfWork->registerDeleted($this->unitOfWork->fetchById(1));
        $this->unitOfWork->registerDeleted($this->unitOfWork->fetchById(2));
        $this->unitOfWork->registerDeleted($this->unitOfWork->fetchById(3));
        $this->unitOfWork->commit();

        $this->assertNull($this->unitOfWork->fetchById(1));
        $this->assertNull($this->unitOfWork->fetchById(2));
        $this->assertNull($this->unitOfWork->fetchById(3));
    }
}
