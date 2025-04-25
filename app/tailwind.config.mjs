/** @type {import('tailwindcss').Config} */
import forms from '@tailwindcss/forms'

export default {
  content: ['./src/**/*.{html,js,svelte,ts}', './index.html'],
  theme: {
    extend: {
      colors: {
        drupal: {
          "navy-blue": "#064771",
          "light-navy-blue": "#0D7DC1",
          "pale-gray": "#F6F6F2"
        }
      }
    }
  },
  plugins: [
    forms,
  ],
}
