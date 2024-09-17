<?php

namespace App\Form;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DossierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('file', FileType::class, [
                'label' => 'Télécharger le dossier',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M', // Limite la taille des fichiers à 10MB
                        'mimeTypes' => [
                            'application/pdf',
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/x-7z-compressed',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel', // .xls
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                            'application/msword', // .doc
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier valide (PDF, image, ZIP, Excel, ou Word).',
                    ])
                ],
            ]);
    }
}