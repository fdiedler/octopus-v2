<?php

namespace App\EasyAdmin;

/**
 * Defines which Symfony role grants access to an EasyAdmin action/entity
 *
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
trait RoleActionMappingTrait
{

    public function getExpectedRole(string $action, string $entityShortName)
    {
        if(in_array($action, [ 'list', 'search', 'show' ])) {
            return 'ROLE_VIEW_'.strtoupper($entityShortName);
        } elseif(in_array($action, [ 'new', 'edit' ])) {
            return 'ROLE_EDIT_' . strtoupper($entityShortName);
        } elseif(in_array($action, [ 'delete' ])) {
            return 'ROLE_DELETE_' . strtoupper($entityShortName);
        } else {
            throw new \LogicException("Unexpected action: $action");
        }
    }

    public function getAllPossibleActions()
    {
        return [
            'list',
            'search',
            'show',
            'new',
            'edit',
            'delete'
        ];
    }

}
