<?php
// src/Serializer/FormErrorSerializer.php

namespace App\Serializer;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class FormErrorSerializer implements ContextAwareNormalizerInterface
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof FormInterface;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return $this->getErrors($object);
    }

    private function getErrors(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            if ($error instanceof FormError) {
                $propertyPath = $error->getCause()->getPropertyPath();
                $message = $error->getMessage();

                $normalizedError = [
                    'message' => $message,
                ];

                // Extract property name from property path
                $propertyName = $this->extractPropertyName($propertyPath);
                if ($propertyName) {
                    $errors[$propertyName][] = $normalizedError;
                } else {
                    $errors[] = $normalizedError;
                }
            }
        }

        foreach ($form->all() as $child) {
            if ($child instanceof FormInterface) {
                $childErrors = $this->getErrors($child);
                $errors = array_merge($errors, $childErrors);
            }
        }

        return $errors;
    }

    private function extractPropertyName($propertyPath)
    {
        $matches = [];
        preg_match('/children\[([^\]]+)\]/', $propertyPath, $matches);

        return $matches[1] ?? null;
    }
}
