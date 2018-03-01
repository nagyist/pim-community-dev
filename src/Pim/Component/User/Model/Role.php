<?php

namespace Pim\Component\User\Model;

use Pim\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Role\Role as SymfonyRole;

/**
 * @author    Arnaud Langlade <arnaud.langlade@akeneo.com>
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @todo This "write" model should not extends Symfony\Component\Security\Core\Role\Role.We should create a "read"
 * model that extends that class.
 *
 * For now, this model MUST extends Symfony\Component\Security\Core\Role\Role because the symfony security component
 * do some stuff if the role is a instance of this class. You should have a look to
 * Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity for instance
 */
class Role extends SymfonyRole implements RoleInterface
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $role;

    /** @var string */
    protected $label;

    /**
     * Populate the role field
     *
     * @param string $role ROLE_FOO etc
     */
    public function __construct($role = '')
    {
        $this->role = $role;
        $this->label = $role;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function setRole($role)
    {
        $this->role = (string) strtoupper($role);

        // every role should be prefixed with 'ROLE_'
        if (strpos($this->role, 'ROLE_') !== 0 && User::ROLE_ANONYMOUS !== $role) {
            $this->role = 'ROLE_' . $this->role;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;

        return $this;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->role;
    }
}
