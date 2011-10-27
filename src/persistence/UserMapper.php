<?php
class UserMapper implements MapperInterface
{
    /**
     * @var PDO The database resource.
     */
    protected $db;

    /**
     * @var IdentityMap
     */
    protected $identityMap;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db          = $db;
        $this->identityMap = new IdentityMap();
    }

    /**
     * @return array
     */
    public function getAllIds()
    {
        $result = array();

        foreach ($this->db->query('SELECT id FROM tbl_user') as $row)
        {
            $result[] = $row['id'];
        }

        return $result;
    }

    /**
     * @param integer $id
     * @throws OutOfBoundsException
     * @return User
     */
    public function findById($id)
    {
        if (true === $this->identityMap->hasId($id))
        {
            return $this->identityMap->getObject($id);
        }

        $sth = $this->db->prepare('SELECT * FROM tbl_user WHERE id = :id');
        $sth->bindValue(':id', $id, PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 0)
        {
            throw new OutOfBoundsException(
              sprintf('No user with id #%d exists.', $id)
            );
        }

        $object = $sth->fetchObject();
        $user = new User();

        $user
          ->setPassword($object->password)
          ->setNickname($object->nickname);

        $this->identityMap->set($id, $user);

        return $user;
    }

    /**
     * @param User $user
     * @throws MapperException
     * @return integer A lastInsertId.
     */
    public function insert(User $user)
    {
        if (true === $this->identityMap->hasObject($user))
        {
            throw new MapperException('Object has an ID, cannot insert.');
        }

        $sth = $this->db->prepare(
        	"INSERT INTO tbl_user (nickname, `password`) VALUES(':nick', ':passwd')"
        );

        $sth->bindValue(':nick', $user->getNickname(), PDO::PARAM_STR);
        $sth->bindValue(':passwd', $user->getPassword(), PDO::PARAM_STR);
        $sth->execute();

        $this->identityMap->set((int)$this->db->lastInsertId(), $user);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param User $user
     * @throws MapperException
     * @return boolean
     */
    public function update(User $user)
    {
        if (false === $this->identityMap->hasObject($user))
        {
            throw new MapperException('Object has no ID, cannot update.');
        }

        $sth = $this->db->prepare(
        	"UPDATE tbl_user SET nickname = :nick, `password` = :passwd WHERE id = :id"
        );

        $sth->bindValue(':nick', $user->getNickname(), PDO::PARAM_STR);
        $sth->bindValue(':passwd', $user->getPassword(), PDO::PARAM_STR);
        $sth->bindValue(':id', $this->identityMap->getId($user), PDO::PARAM_INT);

        return $sth->execute();
    }

    /**
     * @param User $user
     * @throws MapperException
     * @return boolean
     */
    public function delete(User $user)
    {
        if (false === $this->identityMap->hasObject($user))
        {
            throw new MapperException('Object has no ID, cannot delete.');
        }

        $sth = $this->db->prepare(
        	"DELETE FROM tbl_user WHERE id = :id;"
        );

        $sth->bindValue(':id', $this->identityMap->getId($user), PDO::PARAM_INT);

        return $sth->execute();
    }
}