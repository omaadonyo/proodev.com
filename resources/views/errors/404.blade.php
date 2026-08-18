<x-error-page
    code="404"
    title="Page not found"
    message="The page you're looking for doesn't exist or has moved. Let's get you back on track."
    primaryUrl="{{ url('/') }}"
    primaryLabel="Back to home"
    secondaryUrl="{{ auth()->check() ? route('home') : route('jobs.index') }}"
    secondaryLabel="{{ auth()->check() ? 'Go to my feed' : 'Discover engineers' }}"
/>
