// Version comparison helpers for Drupal.org release tags, which mix plain
// semver (1.0.1), core-prefixed tags (8.x-1.17), and pre-release suffixes
// (-alpha1, -beta2, -rc1, -dev).

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

export function findPreviousVersion(version, tags) {
  if (!tags || tags.length === 0) {
    return '';
  }

  // Check if the input version has a prefix
  const hasPrefix = version.match(/^\d+\.x-/);
  const prefix = hasPrefix ? hasPrefix[0] : '';

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

// Compare two versions to determine if versionA is newer than versionB
export function isVersionNewer(versionA, versionB) {
  if (!versionA || !versionB) {
    return false;
  }

  // Strip prefixes like "8.x-" for comparison
  const versionACompare = versionA.replace(/^\d+\.x-/, '');
  const versionBCompare = versionB.replace(/^\d+\.x-/, '');

  // Compare major.minor.patch parts numerically
  const aBase = versionACompare.split('-')[0];
  const bBase = versionBCompare.split('-')[0];

  const baseComparison = aBase.localeCompare(bBase, undefined, {
    numeric: true,
    sensitivity: 'base'
  });

  // If base versions are different, return the comparison result
  if (baseComparison !== 0) {
    return baseComparison > 0;
  }

  // If base versions are the same, compare by stability weight
  const aWeight = getPreReleaseWeight(versionA);
  const bWeight = getPreReleaseWeight(versionB);

  return aWeight > bWeight;
}
