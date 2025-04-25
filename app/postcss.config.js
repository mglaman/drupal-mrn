import tailwind from 'tailwindcss'
import tailwindConfig from './tailwind.config.mjs'
import autoprefixer from 'autoprefixer'

export default {
  plugins: [
    tailwind(tailwindConfig),
    autoprefixer
  ]
}
