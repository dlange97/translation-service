<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TranslationRepository;
use App\Traits\HasInstanceId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'translation')]
#[ORM\UniqueConstraint(name: 'UNQ_LOCALE_GROUP', columns: ['locale', 'translation_group_id'])]
#[ORM\HasLifecycleCallbacks]
class Translation
{
    use HasInstanceId;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 5)]
    #[Assert\NotBlank(message: 'Locale is required.')]
    #[Assert\Regex(
        pattern: '/^[a-z]{2}(?:-[a-z]{2})?$/',
        message: 'Locale must use format like "en", "pl" or "pt-br".',
    )]
    private string $locale = 'en';

    #[ORM\ManyToOne(targetEntity: TranslationGroup::class)]
    #[ORM\JoinColumn(name: 'translation_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Translation group is required.')]
    private ?TranslationGroup $group = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Translation value is required.')]
    private string $translationValue = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = strtolower(trim($locale));
        return $this;
    }

    public function getGroup(): ?TranslationGroup
    {
        return $this->group;
    }

    public function setGroup(TranslationGroup $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getTranslationKey(): string
    {
        return $this->group?->getTranslationKey() ?? '';
    }

    public function getTranslationValue(): string
    {
        return $this->translationValue;
    }

    public function setTranslationValue(string $value): static
    {
        $this->translationValue = $value;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
