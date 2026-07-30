import { describe, it, expect } from 'vitest'
import { findPreviousVersion, isVersionNewer } from '../src/lib/versions.js'

const tags = (...names) => names.map(name => ({ name }))

describe('findPreviousVersion', () => {
  it('returns empty string when there are no tags', () => {
    expect(findPreviousVersion('1.0.1', [])).toBe('')
    expect(findPreviousVersion('1.0.1', undefined)).toBe('')
  })

  it('returns empty string when the version is not a known tag', () => {
    expect(findPreviousVersion('9.9.9', tags('1.0.0', '1.0.1'))).toBe('')
  })

  it('returns empty string when the version is the oldest tag', () => {
    expect(findPreviousVersion('1.0.0', tags('1.0.0', '1.0.1'))).toBe('')
  })

  it('finds the previous patch release', () => {
    expect(findPreviousVersion('1.0.2', tags('1.0.0', '1.0.1', '1.0.2'))).toBe('1.0.1')
  })

  it('sorts versions numerically, not alphabetically', () => {
    expect(findPreviousVersion('1.10.0', tags('1.2.0', '1.9.0', '1.10.0'))).toBe('1.9.0')
  })

  it('finds the previous release across minor versions', () => {
    expect(findPreviousVersion('1.1.0', tags('1.0.0', '1.0.4', '1.1.0'))).toBe('1.0.4')
  })

  it('handles 8.x- prefixed tags', () => {
    expect(findPreviousVersion('8.x-1.17', tags('8.x-1.15', '8.x-1.16', '8.x-1.17'))).toBe('8.x-1.16')
  })

  it('prefers a previous tag with the same core prefix', () => {
    expect(
      findPreviousVersion('8.x-1.2', tags('7.x-1.1', '8.x-1.1', '7.x-1.2', '8.x-1.2'))
    ).toBe('8.x-1.1')
  })

  it('orders pre-releases before the stable release', () => {
    expect(
      findPreviousVersion('1.0.0', tags('1.0.0-alpha1', '1.0.0-beta1', '1.0.0-rc1', '1.0.0'))
    ).toBe('1.0.0-rc1')
  })

  it('orders pre-release types alpha < beta < rc', () => {
    expect(
      findPreviousVersion('1.0.0-rc1', tags('1.0.0-alpha1', '1.0.0-beta1', '1.0.0-rc1'))
    ).toBe('1.0.0-beta1')
  })

  it('orders numbered pre-releases within a type', () => {
    expect(
      findPreviousVersion('1.0.0-beta2', tags('1.0.0-beta1', '1.0.0-beta2', '1.0.0-alpha3'))
    ).toBe('1.0.0-beta1')
  })

  it('finds the previous stable release for the next pre-release', () => {
    expect(
      findPreviousVersion('2.0.0-alpha1', tags('1.0.0', '2.0.0-alpha1'))
    ).toBe('1.0.0')
  })
})

describe('isVersionNewer', () => {
  it('is false when either version is empty', () => {
    expect(isVersionNewer('', '1.0.0')).toBe(false)
    expect(isVersionNewer('1.0.0', '')).toBe(false)
    expect(isVersionNewer('', '')).toBe(false)
  })

  it('compares base versions numerically', () => {
    expect(isVersionNewer('1.0.1', '1.0.0')).toBe(true)
    expect(isVersionNewer('1.0.0', '1.0.1')).toBe(false)
    expect(isVersionNewer('1.10.0', '1.9.0')).toBe(true)
  })

  it('is false for identical versions', () => {
    expect(isVersionNewer('1.0.0', '1.0.0')).toBe(false)
  })

  it('ignores 8.x- prefixes when comparing bases', () => {
    expect(isVersionNewer('8.x-1.17', '8.x-1.16')).toBe(true)
    expect(isVersionNewer('8.x-1.16', '8.x-1.17')).toBe(false)
  })

  it('ranks stable above pre-releases of the same base', () => {
    expect(isVersionNewer('1.0.0', '1.0.0-rc1')).toBe(true)
    expect(isVersionNewer('1.0.0-rc1', '1.0.0')).toBe(false)
  })

  it('ranks rc > beta > alpha > dev for the same base', () => {
    expect(isVersionNewer('1.0.0-rc1', '1.0.0-beta1')).toBe(true)
    expect(isVersionNewer('1.0.0-beta1', '1.0.0-alpha1')).toBe(true)
    expect(isVersionNewer('1.0.0-alpha1', '1.0.0-dev')).toBe(true)
    expect(isVersionNewer('1.0.0-beta1', '1.0.0-rc1')).toBe(false)
  })

  it('compares numbered pre-releases within a type', () => {
    expect(isVersionNewer('1.0.0-beta2', '1.0.0-beta1')).toBe(true)
    expect(isVersionNewer('1.0.0-beta1', '1.0.0-beta2')).toBe(false)
  })
})
