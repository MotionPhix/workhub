import { useBreakpoints } from '@vueuse/core'

export function useDeviceDetection() {
  const breakpoints = useBreakpoints({
    mobile: 0,
    tablet: 640,
    laptop: 1024,
    desktop: 1280
  })

  return {
    isMobile: breakpoints.smaller('tablet'),
    isTablet: breakpoints.between('tablet', 'laptop'),
    isDesktop: breakpoints.greaterOrEqual('laptop')
  }
}
