import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * MiningStatCard - Néré Mining themed stat card component
 * 
 * @component
 * @param {string} title - Stat title
 * @param {string|number} value - Stat value
 * @param {string} color - 'gold', 'crimson', 'blue', 'green'
 * @param {number} trend - Trend percentage (positive or negative)
 * @param {string} icon - Icon class
 * @param {string} className - Additional CSS classes
 */
const MiningStatCard = ({
  title,
  value,
  color = 'gold',
  trend,
  icon,
  className,
  ...props
}) => {
  const cardClass = classNames(
    'stat-card-mining',
    {
      'stat-gold': color === 'gold',
      'stat-crimson': color === 'crimson',
      'stat-blue': color === 'blue',
      'stat-green': color === 'green',
      'hover-lift': true,
    },
    className
  );

  const trendClass = classNames(
    'stat-trend-mining',
    {
      'trend-up': trend > 0,
      'trend-down': trend < 0,
    }
  );

  return (
    <div className={cardClass} {...props}>
      <div className="d-flex justify-content-between align-items-start mb-2">
        {icon && <i className={`stat-icon-mining ${icon}`} />}
      </div>
      
      <div className="stat-title-mining">{title}</div>
      
      <div className="stat-value">{value}</div>
      
      {trend !== undefined && (
        <div className={trendClass}>
          {trend > 0 ? (
            <i className="la la-arrow-up mr-1" />
          ) : (
            <i className="la la-arrow-down mr-1" />
          )}
          {Math.abs(trend)}%
        </div>
      )}
    </div>
  );
};

MiningStatCard.propTypes = {
  title: PropTypes.string.isRequired,
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  color: PropTypes.oneOf(['gold', 'crimson', 'blue', 'green']),
  trend: PropTypes.number,
  icon: PropTypes.string,
  className: PropTypes.string,
};

export default MiningStatCard;
