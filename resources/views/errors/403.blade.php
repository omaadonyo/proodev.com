<x-error-page
    code="403"
    title="Access denied"
    message="You don't have permission to view this page. If you believe this is a mistake, please contact support."
    primaryUrl="{{ url('/') }}"
    primaryLabel="Back to home"
    secondaryUrl="{{ auth()->check() ? '' : route('login') }}"
    secondaryLabel="{{ auth()->check() ? '' : 'Sign in' }}"
/>
