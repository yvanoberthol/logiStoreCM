<?php


namespace App\Dto;


use App\Entity\Permission;

class PermissionDto
{
    public $id;
    public $code;
    public $link;
    public $shortcut;
    public $shortcutKey;

    public static function createFromEntity(Permission $entity): self {
        $permission = new self();
        $permission->id = $entity->getId();
        $permission->code = $entity->getCode();
        $permission->link = strtolower($entity->getCode());
        $permission->shortcut = $entity->getShortcut();
        if ($entity->getShortcut() !== null && $entity->getShortcut() !== ''){
            $permission->shortcutKey = $entity->getShortcutKey();
        }

        return $permission;
    }


}
