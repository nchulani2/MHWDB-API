<?php
	namespace App\Search;

	use App\Search\Exception\CannotDirectlySearchRelationshipException;
	use App\Search\Exception\UnknownFieldException;
	use Doctrine\Common\Persistence\Mapping\ClassMetadata;
	use Doctrine\DBAL\Types\Type;
	use Doctrine\ORM\QueryBuilder;
	use Symfony\Bridge\Doctrine\RegistryInterface;

	class FieldResolver {
		/**
		 * @var \Doctrine\Common\Persistence\ObjectManager
		 */
		protected $entityManager;

		/**
		 * @var QueryBuilder
		 */
		protected $queryBuilder;

		/**
		 * @var ClassMetadata
		 */
		protected $rootMetadata;

		/**
		 * @var string
		 */
		protected $rootAlias;

		/**
		 * @var string[]
		 */
		protected $joins = [];

		/**
		 * @var array
		 */
		protected $resolveCache = [];

		/**
		 * FieldResolver constructor.
		 *
		 * @param RegistryInterface $registry
		 * @param QueryBuilder      $queryBuilder
		 */
		public function __construct(RegistryInterface $registry, QueryBuilder $queryBuilder) {
			$this->entityManager = $registry->getManager();
			$this->queryBuilder = $queryBuilder;

			$this->rootMetadata = $this->entityManager->getClassMetadata($queryBuilder->getRootEntities()[0]);
			$this->rootAlias = $queryBuilder->getRootAliases()[0];
		}

		/**
		 * @param string $field
		 *
		 * @return FieldInfo
		 * @throws CannotDirectlySearchRelationshipException
		 * @throws UnknownFieldException
		 */
		public function resolve(string $field): FieldInfo {
			if (isset($this->resolveCache[$field]))
				return $this->resolveCache[$field];

			$parts = explode('.', $field);
			$actualField = array_pop($parts);

			if (!sizeof($parts)) {
				if ($this->rootMetadata->hasAssociation($actualField))
					throw new CannotDirectlySearchRelationshipException($actualField);

				return $actualField;
			}

			// skills.level
			// skills.skill.name

			$metadata = $this->rootMetadata;
			$alias = $this->rootAlias;

			foreach ($parts as $part) {
				if (!$metadata->hasAssociation($part))
					throw new UnknownFieldException($field);

				$metadata = $this->entityManager->getClassMetadata($metadata->getAssociationTargetClass($part));
				$alias = $this->getJoinAlias($alias, $part);
			}

			$isJson = $metadata->getTypeOfField($actualField) === Type::JSON;

			return $this->resolveCache[$field] = new FieldInfo($alias . '.' . $actualField, $isJson);
		}

		/**
		 * @param string $parentAlias
		 * @param string $parentField
		 *
		 * @return string
		 */
		protected function getJoinAlias(string $parentAlias, string $parentField): string {
			$joinKey = $parentAlias . '.' . $parentField;

			if (isset($this->joins[$joinKey]))
				return $this->joins[$joinKey];

			$alias = 'join_' . sizeof($this->joins);

			$this->queryBuilder->leftJoin($joinKey, $alias);

			return $this->joins[$joinKey] = $alias;
		}
	}