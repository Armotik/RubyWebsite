<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->setEntityLabelInSingular('Staff User')
            ->setEntityLabelInPlural('Staff Users')
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityPermission('ROLE_WEBMASTER')
            ->setPageTitle('index', 'NG OP Staff Panel - %entity_label_plural%')
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('username', "Username"),
            ChoiceField::new('roles', 'Roles')
                ->setChoices([
                    'MOD TEST' => 'ROLE_MOD_TEST',
                    'MOD' => 'ROLE_MOD',
                    'MOD +' => 'ROLE_MOD_PLUS',
                    'SUPER MOD' => 'ROLE_SUPER_MOD',
                    'ADMIN' => 'ROLE_ADMI N',
                    'WEBMASTER' => 'ROLE_WEBMASTER',
                    'ROLEPLAY' => 'ROLE_ROLEPLAY',
                    'BUILDER' => 'ROLE_BUILDER',
                    'BOT' => 'ROLE_BOT',
                    'JOURNALIST' => 'ROLE_JOURNALIST',
                    'GUIDE' => 'ROLE_GUIDE',
                ])
                ->allowMultipleChoices()
                ->setRequired(true)
                ->setHelp('Choose the role of the user'),
            TextField::new('password', 'Password')
                ->setRequired(true)
                ->setFormType(PasswordType::class)
                ->setHelp('Enter the password of the user')
                ->onlyOnForms(),
        ];
    }
}
