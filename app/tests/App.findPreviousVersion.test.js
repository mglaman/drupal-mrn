import { describe, it, expect } from 'vitest'

/**
 * Test the findPreviousVersion logic in isolation
 * This tests the version finding algorithm used in App.svelte
 */
describe('findPreviousVersion logic', () => {
  const findPreviousVersion = (version, tags) => {
    if (!tags || tags.length === 0) {
      return '';
    }

    // Check if the input version has a prefix
    const hasPrefix = version.match(/^\d+\.x-/);
    const prefix = hasPrefix ? hasPrefix[0] : '';

    // Helper function to extract numeric suffix from pre-release identifiers
    function getPreReleaseNumber(versionStr) {
      const match = versionStr.match(/(alpha|beta|rc)(\d+)/);
      return match ? parseInt(match[2], 10) : 0;
    }

    // Helper function to get sort weight for pre-release versions
    function getPreReleaseWeight(versionStr) {
      // Default weight for stable versions
      let weight = 1000;

      // Check for pre-release identifiers
      if (versionStr.includes('-alpha')) weight = 100;
      else if (versionStr.includes('-beta')) weight = 200;
      else if (versionStr.includes('-rc')) weight = 300;
      else if (versionStr.includes('-dev')) weight = 50;

      // Add the numeric suffix to the weight for proper sorting within a pre-release type
      const preReleaseNum = getPreReleaseNumber(versionStr);
      weight += preReleaseNum;

      return weight;
    }

    // Create a sorted list of version tags
    const sortedTags = [...tags]
      .map(tag => ({
        ...tag,
        // Store original name
        originalName: tag.name,
        // Strip prefixes like "8.x-" for comparison
        compareValue: tag.name.replace(/^\d+\.x-/, ''),
        // Store the prefix if any
        prefix: tag.name.match(/^\d+\.x-/) ? tag.name.match(/^\d+\.x-/)[0] : '',
        // Calculate weight for sorting pre-release versions
        preReleaseWeight: getPreReleaseWeight(tag.name)
      }))
      .sort((a, b) => {
        // First compare major.minor.patch parts numerically
        const aBase = a.compareValue.split('-')[0];
        const bBase = b.compareValue.split('-')[0];

        const baseComparison = aBase.localeCompare(bBase, undefined, {
          numeric: true,
          sensitivity: 'base'
        });

        // If base versions are the same, compare by stability (stable > rc > beta > alpha)
        if (baseComparison === 0) {
          return a.preReleaseWeight - b.preReleaseWeight;
        }

        return baseComparison;
      });

    // Find the current version in the sorted list
    const currentVersionStripped = version.replace(/^\d+\.x-/, '');

    // First try to find a match with the same prefix
    let currentIndex = -1;

    if (prefix) {
      // If input has prefix, first look for exact same prefix
      currentIndex = sortedTags.findIndex(tag =>
        tag.compareValue === currentVersionStripped && tag.prefix === prefix);
    }

    // If no match with same prefix or no prefix in input, find by version only
    if (currentIndex === -1) {
      currentIndex = sortedTags.findIndex(tag =>
        tag.compareValue === currentVersionStripped);
    }

    if (currentIndex > 0) {
      // Try to find previous version with the same prefix first
      if (prefix) {
        for (let i = currentIndex - 1; i >= 0; i--) {
          if (sortedTags[i].prefix === prefix) {
            return sortedTags[i].originalName;
          }
        }
      }

      // Otherwise return the immediate predecessor
      return sortedTags[currentIndex - 1].originalName;
    }

    return '';
  }

  it('returns empty string when no tags provided', () => {
    expect(findPreviousVersion('1.0.0', [])).toBe('')
    expect(findPreviousVersion('1.0.0', null)).toBe('')
  })

  it('finds previous version in simple list', () => {
    const tags = [
      { name: '1.0.0' },
      { name: '1.0.1' },
      { name: '1.0.2' }
    ]
    expect(findPreviousVersion('1.0.2', tags)).toBe('1.0.1')
    expect(findPreviousVersion('1.0.1', tags)).toBe('1.0.0')
  })

  it('handles Drupal-style prefixed versions', () => {
    const tags = [
      { name: '8.x-1.0.0' },
      { name: '8.x-1.0.1' },
      { name: '8.x-1.0.2' },
      { name: '8.x-1.1.0' }
    ]
    expect(findPreviousVersion('8.x-1.0.2', tags)).toBe('8.x-1.0.1')
    expect(findPreviousVersion('8.x-1.1.0', tags)).toBe('8.x-1.0.2')
  })

  it('handles pre-release versions correctly', () => {
    const tags = [
      { name: '1.0.0-alpha1' },
      { name: '1.0.0-alpha2' },
      { name: '1.0.0-beta1' },
      { name: '1.0.0-rc1' },
      { name: '1.0.0' }
    ]
    expect(findPreviousVersion('1.0.0', tags)).toBe('1.0.0-rc1')
    expect(findPreviousVersion('1.0.0-rc1', tags)).toBe('1.0.0-beta1')
    expect(findPreviousVersion('1.0.0-beta1', tags)).toBe('1.0.0-alpha2')
    expect(findPreviousVersion('1.0.0-alpha2', tags)).toBe('1.0.0-alpha1')
  })

  it('returns empty string when version is first in list', () => {
    const tags = [
      { name: '1.0.0' },
      { name: '1.0.1' }
    ]
    expect(findPreviousVersion('1.0.0', tags)).toBe('')
  })

  it('handles mixed prefixed and non-prefixed versions', () => {
    const tags = [
      { name: '1.0.0' },
      { name: '8.x-1.0.0' },
      { name: '8.x-1.0.1' }
    ]
    // Should prefer same prefix
    expect(findPreviousVersion('8.x-1.0.1', tags)).toBe('8.x-1.0.0')
  })

  it('sorts versions correctly with different patch levels', () => {
    const tags = [
      { name: '1.0.0' },
      { name: '1.0.1' },
      { name: '1.0.10' },
      { name: '1.0.2' }
    ]
    expect(findPreviousVersion('1.0.10', tags)).toBe('1.0.2')
  })
})

