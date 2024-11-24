<?php

namespace App\Controller\Admin;

use App\Entity\BlogPost;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BlogPostCrudController extends AbstractCrudController {
    public static function getEntityFqcn(): string {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud {
        return $crud
            ->setEntityLabelInSingular('Post')
            ->setEntityLabelInPlural('Posts')
            ->setEntityPermission('ROLE_ADMIN')
        ;
    }

    public function createEntity(string $entityFqcn) {
        $blogPost = new BlogPost();
        $blogPost->setPublicationDate((new DateTime('now')));
        return $blogPost;
    }

    public function configureFields(string $pageName): iterable {
        return [
            TextField::new('title'),
            TextEditorField::new('content')
            ->addJsFiles(Asset::new('/scripts/trix-upload.js')->onlyOnForms()),
            DateTimeField::new('publicationDate')
                ->hideOnForm(),
            ImageField::new('bannerPath')
                ->setBasePath('uploads/')
                ->setUploadDir('public/uploads')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            AssociationField::new('category')->autocomplete()->setSortProperty('name')
        ];
    }

    public function configureAssets(Assets $assets): Assets {
        return $assets
            ->addCssFile('styles/admin/blog_crud.css')
        ;
    }

    #[Route("/admin/upload/trix", name: "admin_trix_upload")]
    #[IsGranted('ROLE_ADMIN')]
    public function upload(Request $request, ValidatorInterface $validator): Response {
        $uploadedFile = $request->files->get('file');

        $violations = $validator->validate($uploadedFile, [
            new NotBlank(),
            new File(['mimeTypes' => ['image/*']], '10000k')
        ]);

        if ($violations->count() > 0) {
            return $this->json([
                'ok' => false,
                'error' => (string)$violations,
            ]);
        }

        $newFilename = date('Y-m-d') . '_' . uniqid() . '.' . $uploadedFile->guessExtension();

        $uploadedFile->move('uploads/trix', $newFilename);

        return $this->json([
            'ok' => true,
            'url' => 'uploads/trix/' . $newFilename,
        ]);
    }

}
