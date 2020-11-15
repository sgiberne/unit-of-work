<?php

namespace Sgiberne\UnitOfWork\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Sgiberne\UnitOfWork\Adapter\PDOAdapter;
use Sgiberne\UnitOfWork\Collection\EntityCollection;
use Sgiberne\UnitOfWork\Storage\ObjectStorage;
use Sgiberne\UnitOfWork\Tests\Data\DataMapper\ActorDataMapper;
use Sgiberne\UnitOfWork\Tests\Data\Entity\Actor;
use Sgiberne\UnitOfWork\Tests\DatabasePhpunitTestPdoCreator;
use Sgiberne\UnitOfWork\UnitOfWork;

final class UnitOfWorkTest extends TestCase
{

    private ?UnitOfWork $unitOfWork;

    public static function setUpBeforeClass(): void
    {
        $databasePhpunitTest = new DatabasePhpunitTestPdoCreator();
        $databasePhpunitTest->createPhpunitTestDatabase();
        $databasePhpunitTest->createActorTable();
    }

    public static function tearDownAfterClass(): void
    {
        (new DatabasePhpunitTestPdoCreator())->dropDatabase();
    }

    public function setUp(): void
    {
        $databaseName = DatabasePhpunitTestPdoCreator::DATABASE_NAME;

        $adapter = new PDOAdapter(
            sprintf('mysql:dbname=%s;host=%s;port=%s', DatabasePhpunitTestPdoCreator::DATABASE_NAME, DatabasePhpunitTestPdoCreator::DATABASE_HOST, DatabasePhpunitTestPdoCreator::DATABASE_PORT),
            DatabasePhpunitTestPdoCreator::DATABASE_USER,
            DatabasePhpunitTestPdoCreator::DATABASE_PASSWORD
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
        $actorsFromDatabase = $this->unitOfWork->fetchAll([], "", [], ['id' => 'ASC']);

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
