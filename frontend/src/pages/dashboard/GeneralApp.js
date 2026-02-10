// @mui
import { Container, Grid, Stack } from '@mui/material';
import swal from 'sweetalert'
import { useState, useEffect } from 'react';

// hooks
import useAuth from '../../hooks/useAuth';
import useSettings from '../../hooks/useSettings';
// components
import Page from '../../components/Page';
import { PATH_DASHBOARD } from '../../routes/paths';
import axios from '../../utils/axios';
// sections
import {
  Databalance,
  Appbalance,
  Userverified,
  Appcakages,
  Upgrade,
  AppWelcome,
  AppMarquee,
  Referral
} from '../../sections/@dashboard/general/app';
// icons
import DataWifi from '../../assets/data_wifi';
import AirtimeIcon from '../../assets/airtime';
import AirtimeCashIcon from '../../assets/airtime2cash';
import ElectricIcon from '../../assets/electricity';
import SmartIcon from '../../assets/smart-tv';
import TransferIcon from '../../assets/money-card';
import BulkSmsIcon from '../../assets/news';
import ResultCheck from '../../assets/resultcheck';
import DataCard from '../../assets/data_card';
import RechargeCard from '../../assets/recharge_card';
import useResponsive from '../../hooks/useResponsive';

// ----------------------------------------------------------------------

import AppFeatured from '../../sections/@dashboard/general/app/AppFeatured';

export default function GeneralApp() {
  const { user, setting } = useAuth();
  const { themeStretch } = useSettings();
  const [datapurchase, Setdatapurchase] = useState();
  const [marqueeMessage, setMarqueeMessage] = useState('');
  const isDesktop = useResponsive('up', 'lg');

  useEffect(() => {
    if (marqueeMessage) {
      // Check if user has already seen the welcome message
      const hasSeenWelcome = localStorage.getItem('hasSeenWelcome_v2');

      if (!hasSeenWelcome) {
        swal({
          content: {
            element: 'div',
            attributes: {
              innerHTML: `
                <div style="text-align: center; padding: 10px 0;">
                  <div style="
                    width: 70px;
                    height: 70px;
                    margin: 0 auto 15px;
                    background: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    overflow: hidden;
                  ">
                    <img src="/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;" />
                  </div>
                  <div style="
                    color: white;
                    font-size: 15px;
                    line-height: 1.6;
                    text-align: center;
                    padding: 0 15px;
                    margin-bottom: 10px;
                  ">
                    ${marqueeMessage}
                  </div>
                </div>
              `
            }
          },
          buttons: {
            confirm: {
              text: 'OK',
              value: true,
              visible: true,
              className: 'swal-button-custom-blue',
              closeModal: true,
            }
          },
          className: 'swal-custom-welcome-blue',
          closeOnClickOutside: false,
        }).then((value) => {
          if (value) {
            // Mark welcome as seen
            localStorage.setItem('hasSeenWelcome_v2', 'true');
          }
        });

        // Add custom styles
        const style = document.createElement('style');
        style.innerHTML = `
          .swal-overlay {
            background-color: rgba(0, 0, 0, 0.6) !important;
            z-index: 9999 !important;
          }
          .swal-custom-welcome-blue {
            border-radius: 20px !important;
            padding: 0 !important;
            max-width: 450px !important;
            width: 90% !important;
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%) !important;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.4) !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            position: relative !important;
            overflow: hidden !important;
            z-index: 10000 !important;
          }
          .swal-custom-welcome-blue::before {
            content: '' !important;
            position: absolute !important;
            top: -50% !important;
            right: -50% !important;
            width: 200% !important;
            height: 200% !important;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%) !important;
            pointer-events: none !important;
          }
          .swal-custom-welcome-green .swal-content {
            padding: 25px 15px 15px !important;
            text-align: center !important;
            background: transparent !important;
          }
          .swal-custom-welcome-blue .swal-footer {
            text-align: center !important;
            padding: 15px 20px 25px !important;
            background: transparent !important;
          }
          .swal-button-custom-blue {
            background-color: white !important;
            color: #2563EB !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 12px 50px !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
          }
          .swal-button-custom-blue:hover {
            background-color: #f0f0f0 !important;
            transform: translateY(-2px) scale(1.05) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
          }
          .swal-button-custom-green:active {
            transform: translateY(0) scale(1) !important;
          }
          @media (max-width: 600px) {
            .swal-custom-welcome-blue {
              max-width: 340px !important;
              width: 85% !important;
            }
            .swal-custom-welcome-blue .swal-content {
              padding: 20px 10px 10px !important;
              font-size: 14px !important;
            }
            .swal-button-custom-blue {
              padding: 10px 40px !important;
              font-size: 14px !important;
            }
          }
        `;
        document.head.appendChild(style);
      }
    }
  }, [marqueeMessage]);


  const AccessToken = window.localStorage.getItem('accessToken');
  useEffect(() => {
    axios.get(`/api/total/data/purchase/${AccessToken}/secure`).then((data) => Setdatapurchase(data.data?.data_purchased_volume))
  }, []);

  useEffect(() => {
    const fetchWelcome = async () => {
      try {
        const response = await axios.get('/api/secure/welcome');
        if (response.data && response.data.notif_message) {
          setMarqueeMessage(response.data.notif_message);
        }
      } catch (error) {
        console.error('Failed to fetch welcome message:', error);
      }
    };
    fetchWelcome();
  }, []);

  return (
    <Page title="Dashboard">

      <Container maxWidth={themeStretch ? false : 'xl'}>
        <Grid container spacing={isDesktop ? 3 : 2}>



          {/* New Welcome Section */}
          <Grid item xs={12} md={12}>
            <AppWelcome displayName={user?.username} />
          </Grid>

          {/* Feature Carousel (Ads) */}


          {/* Scrolling Marquee */}
          <Grid item xs={12}>
            <AppMarquee message={marqueeMessage} />
          </Grid>

          {/* User Verification Status */}
          <Grid item xs={12}>
            <Userverified displayName={user?.username} bool={user?.type === 'ADMIN'} isVerified={user?.is_bvn} />
          </Grid>

          <Grid item xs={12}>
            <Referral />
          </Grid>

          <Upgrade />

          <Grid item xs={12} md={4}><Appbalance title="Wallet Balance" total={user.bal} /></Grid>
          <Grid item xs={12} md={4}><Appbalance title="Earning Balance" total={user.refbal} /></Grid>
          <Grid item xs={12} md={4}>
            <Databalance title="Data Purchased Today" total={datapurchase !== undefined ? datapurchase : '...'} />
          </Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<DataWifi />} displayname="Buy Data" link={PATH_DASHBOARD.general.buydata} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<AirtimeIcon />} displayname="Buy Airtime" link={PATH_DASHBOARD.general.buyairtime} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<AirtimeCashIcon />} displayname="Airtime 2 Cash" link={PATH_DASHBOARD.general.cash} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<ElectricIcon />} displayname="Electricity Bill" link={PATH_DASHBOARD.general.buybill} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<SmartIcon />} displayname="Cable Subscription" link={PATH_DASHBOARD.general.buycable} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<TransferIcon />} displayname="Bonus Transfer" link={PATH_DASHBOARD.general.earning} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<BulkSmsIcon />} displayname="Bulk SMS" link={PATH_DASHBOARD.general.bulksms} /></Grid>
          <Grid item xs={6} md={6} lg={3}><Appcakages image={<ResultCheck />} displayname="Result Checker" link={PATH_DASHBOARD.general.exam} /></Grid>
          {setting?.setting?.data_card === 1 && <Grid item xs={6} md={6} lg={3}><Appcakages image={<DataCard />} displayname="Data Card Printing" link={PATH_DASHBOARD.general.data_card} /></Grid>}
          {setting?.setting?.recharge_card === 1 && <Grid item xs={6} md={6} lg={3}><Appcakages image={<RechargeCard />} displayname="Recharge Card Printing" link={PATH_DASHBOARD.general.recharge_card} /></Grid>}


        </Grid>
      </Container>
    </Page>
  );
}
