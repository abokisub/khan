import PropTypes from 'prop-types';
// @mui
import { Box, Card, Typography } from '@mui/material';

// ----------------------------------------------------------------------

Databalance.propTypes = {
  title: PropTypes.string.isRequired,
  total: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
};

export default function Databalance({ title, total }) {
  const gradient = (theme) => `linear-gradient(135deg, ${theme.palette.primary.main} 0%, ${theme.palette.primary.darker} 100%)`;

  return (
    <Card sx={{
      display: 'flex',
      alignItems: 'center',
      p: 3,
      background: gradient,
      color: '#fff',
      borderRadius: 2,
      boxShadow: (theme) => theme.customShadows.z4,
      position: 'relative',
      overflow: 'hidden',
      height: '100%'
    }}>
      <Box sx={{ flexGrow: 1, zIndex: 2 }}>
        <Typography variant="subtitle2" sx={{ opacity: 0.9, fontWeight: 700, letterSpacing: 1, mb: 1 }}>
          {title}
        </Typography>
        <Typography variant="h3" sx={{ fontWeight: 800 }}>
          {total}
        </Typography>
      </Box>

      {/* Decorative overlaid circle */}
      <Box
        sx={{
          position: 'absolute',
          bottom: -30,
          right: -30,
          width: 140,
          height: 140,
          borderRadius: '50%',
          background: 'rgba(255,255,255,0.1)',
          zIndex: 1
        }}
      />
    </Card>
  );
}
