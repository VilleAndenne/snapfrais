import { Loader } from '@googlemaps/js-api-loader'

let loaderInstance = null
let isLoaded = false

export const loadGoogleMaps = async () => {
  if (isLoaded) return window.google

  if (!loaderInstance) {
    loaderInstance = new Loader({
      apiKey: import.meta.env.VITE_API_GOOGLE,
      version: 'weekly',
      libraries: ['places'],
    })
  }

  await loaderInstance.load()
  isLoaded = true
  return window.google
}
