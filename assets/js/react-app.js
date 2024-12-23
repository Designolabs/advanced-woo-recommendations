(function () {
    const { useEffect, useState, useCallback, memo, useRef } = React;

    /**
     * @typedef {Object} Product
     * @property {number} id
     * @property {string} name
     * @property {string} image
     * @property {string} link
     * @property {string} price_html
     * @property {string} [description]
     * @property {boolean} [in_stock]
     */

    /**
     * Loading spinner component
     * @memo for performance optimization
     */
    const LoadingSpinner = memo(() => (
        <div className="awr-loading-spinner" role="alert" aria-busy="true">
            <div className="spinner" aria-hidden="true"></div>
            <p>{awr_data.strings.loading || 'Loading recommendations...'}</p>
        </div>
    ));

    /**
     * Error message component
     * @param {{ message: string }} props
     * @memo for performance optimization
     */
    const ErrorMessage = memo(({ message }) => (
        <div className="awr-error-message" role="alert">
            <p>{message}</p>
            <button
                onClick={() => window.location.reload()}
                className="awr-retry-button"
                aria-label="Retry loading recommendations"
            >
                {awr_data.strings.retry || 'Retry'}
            </button>
        </div>
    ));

    /**
     * Product card component
     * @param {{ product: Product }} props
     * @memo for performance optimization
     */
    const ProductCard = memo(({ product }) => {
        const imageRef = useRef(null);

        const handleImageError = useCallback((e) => {
            e.target.onerror = null;
            e.target.src = awr_data.placeholder_image || 'placeholder-image.jpg';
        }, []);

        useEffect(() => {
            // Lazy load images with IntersectionObserver
            if (imageRef.current && 'IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            observer.unobserve(img);
                        }
                    });
                });
                observer.observe(imageRef.current);
                return () => observer.disconnect();
            }
        }, []);

        return (
            <div className="awr-product" data-product-id={product.id}>
                <a
                    href={product.link}
                    className="awr-product-link"
                    title={product.name}
                    aria-label={`View details for ${product.name}`}
                >
                    <div className="awr-product-image">
                        <img
                            ref={imageRef}
                            data-src={product.image}
                            alt={product.name}
                            onError={handleImageError}
                            loading="lazy"
                            width="300"
                            height="300"
                        />
                        {product.in_stock === false && (
                            <span className="awr-out-of-stock" aria-label="Product out of stock">
                                {awr_data.strings.out_of_stock || 'Out of Stock'}
                            </span>
                        )}
                    </div>
                    <div className="awr-product-details">
                        <h2>{product.name}</h2>
                        {product.description && (
                            <p className="awr-product-description">
                                {product.description}
                            </p>
                        )}
                        <p
                            className="awr-price"
                            dangerouslySetInnerHTML={{ __html: product.price_html }}
                        />
                    </div>
                </a>
            </div>
        );
    });

    /**
     * Main recommendations component
     */
    function Recommendations() {
        const [products, setProducts] = useState([]);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        const abortControllerRef = useRef(null);
        const [refreshCount, setRefreshCount] = useState(0);


        const fetchRecommendations = useCallback(async () => {
            try {
                // Cancel previous request if it exists
                if (abortControllerRef.current) {
                    abortControllerRef.current.abort();
                }
                abortControllerRef.current = new AbortController();

                setLoading(true);
                const response = await fetch(
                    `${awr_data.apiEndpoint}?user_id=${awr_data.userId}`,
                    {
                        headers: {
                            'X-WP-Nonce': awr_data.nonce,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        signal: abortControllerRef.current.signal
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (!Array.isArray(data)) {
                    throw new Error('Invalid response format');
                }

                setProducts(data);
                setError(null);
            } catch (error) {
                if (error.name === 'AbortError') return;

                console.error('Error fetching recommendations:', error);
                setError(awr_data.strings.error || 'Failed to load recommendations. Please try again later.');
            } finally {
                setLoading(false);
            }
        }, [awr_data.apiEndpoint, awr_data.userId, awr_data.nonce]);

        useEffect(() => {
            fetchRecommendations();

            // Set up auto-refresh every 5 minutes
            const refreshInterval = setInterval(() => {
                setRefreshCount(prevCount => prevCount + 1);
            }, 300000);

            return () => {
                if (abortControllerRef.current) {
                    abortControllerRef.current.abort();
                }
                clearInterval(refreshInterval);
                setProducts([]);
                setLoading(true);
                setError(null);
            };
        }, [fetchRecommendations]);

        useEffect(() => {
            if (refreshCount > 0) {
                fetchRecommendations();
            }
        }, [refreshCount, fetchRecommendations]);


        if (loading) {
            return <LoadingSpinner />;
        }

        if (error) {
            return <ErrorMessage message={error} />;
        }

        return (
            <div className="awr-recommendations" role="region" aria-label="Product Recommendations">
                <div className="awr-recommendations-grid">
                    {products.length > 0 ? (
                        products.map((product) => (
                            <ProductCard
                                key={product.id}
                                product={product}
                            />
                        ))
                    ) : (
                        <div className="awr-no-recommendations" role="status">
                            <p>{awr_data.strings.no_recommendations || 'No recommendations available at this time.'}</p>
                        </div>
                    )}
                </div>
            </div>
        );
    }

    // Wait for DOM to be ready and mount React app
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('awr-recommendations-root');
        if (container) {
            const root = ReactDOM.createRoot(container);
            root.render(
                <React.StrictMode>
                    <Recommendations />
                </React.StrictMode>
            );
        }
    });
})();