<?php

namespace X509\Certificate\Extension;

use ASN1\Type\Constructed\Sequence;
use ASN1\Type\UnspecifiedType;
use X509\Certificate\Extension\CertificatePolicy\PolicyInformation;
use X509\Certificate\Extension\PolicyMappings\PolicyMapping;


/**
 * Implements 'Policy Mappings' certificate extension.
 *
 * @link https://tools.ietf.org/html/rfc5280#section-4.2.1.5
 */
class PolicyMappingsExtension extends Extension implements 
	\Countable, \IteratorAggregate
{
	/**
	 * Policy mappings.
	 *
	 * @var PolicyMapping[] $_mappings
	 */
	protected $_mappings;
	
	/**
	 * Constructor
	 *
	 * @param bool $critical
	 * @param PolicyMapping ...$mappings One or more PolicyMapping objects
	 */
	public function __construct($critical, PolicyMapping ...$mappings) {
		parent::__construct(self::OID_POLICY_MAPPINGS, $critical);
		$this->_mappings = $mappings;
	}
	
	protected static function _fromDER($data, $critical) {
		$mappings = array_map(
			function (UnspecifiedType $el) {
				return PolicyMapping::fromASN1($el->asSequence());
			}, Sequence::fromDER($data)->elements());
		if (!count($mappings)) {
			throw new \UnexpectedValueException(
				"PolicyMappings must have at least one mapping.");
		}
		return new self($critical, ...$mappings);
	}
	
	protected function _valueASN1() {
		if (!count($this->_mappings)) {
			throw new \LogicException("No mappings.");
		}
		$elements = array_map(
			function (PolicyMapping $mapping) {
				return $mapping->toASN1();
			}, $this->_mappings);
		return new Sequence(...$elements);
	}
	
	/**
	 * Get all mappings.
	 *
	 * @return PolicyMapping[]
	 */
	public function mappings() {
		return $this->_mappings;
	}
	
	/**
	 * Check whether policy mappings have anyPolicy mapped.
	 *
	 * RFC 5280 section 4.2.1.5 states that "Policies MUST NOT be mapped either
	 * to or from the special value anyPolicy".
	 *
	 * @return bool
	 */
	public function hasAnyPolicyMapping() {
		foreach ($this->_mappings as $mapping) {
			if ($mapping->issuerDomainPolicy() ==
				 PolicyInformation::OID_ANY_POLICY) {
				return true;
			}
			if ($mapping->subjectDomainPolicy() ==
				 PolicyInformation::OID_ANY_POLICY) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Get the number of mappings.
	 *
	 * @see Countable::count()
	 * @return int
	 */
	public function count() {
		return count($this->_mappings);
	}
	
	/**
	 * Get iterator for policy mappings.
	 *
	 * @see IteratorAggregate::getIterator()
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->_mappings);
	}
}
